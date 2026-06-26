<?php

namespace App\Console\Commands;

use App\Jobs\SyncPendingCloudflareDomain;
use App\Models\Domain;
use Illuminate\Console\Command;

/**
 * Re-dispatches Cloudflare sync for domains stuck in pending state.
 *
 * A domain is considered stuck when it has a Cloudflare hostname ID but has
 * not been verified and either has a recorded error or hasn't been checked
 * recently (indicating the polling job chain stopped prematurely).
 */
class RecoverStuckDomains extends Command
{
    protected $signature = 'domains:recover-stuck
        {--stale-minutes=60 : Consider domains unchecked for this many minutes as stuck}
        {--dry-run : List stuck domains without dispatching jobs}';

    protected $description = 'Re-dispatch Cloudflare sync for domains stuck in pending state';

    public function handle(): int
    {
        $staleMinutes = (int) $this->option('stale-minutes');
        $dryRun = (bool) $this->option('dry-run');
        $cutoff = now()->subMinutes($staleMinutes);

        $stuck = Domain::query()
            ->whereNotNull('cf_hostname_id')
            ->whereNull('verified_at')
            ->where(function ($q) use ($cutoff) {
                $q->whereNotNull('cf_error')
                    ->orWhere('cf_last_checked_at', '<', $cutoff)
                    ->orWhereNull('cf_last_checked_at');
            })
            ->get();

        if ($stuck->isEmpty()) {
            $this->info('No stuck domains found.');

            return self::SUCCESS;
        }

        $this->info("Found {$stuck->count()} stuck domain(s):");
        $this->newLine();

        foreach ($stuck as $domain) {
            $lastChecked = $domain->cf_last_checked_at?->diffForHumans() ?? 'never';
            $error = $domain->cf_error ? " (error: {$domain->cf_error})" : '';

            $this->line("  [{$domain->id}] {$domain->domain} — last checked: {$lastChecked}{$error}");

            if (! $dryRun) {
                SyncPendingCloudflareDomain::dispatch($domain->id);
            }
        }

        $this->newLine();

        if ($dryRun) {
            $this->info('Dry run — no jobs dispatched.');
        } else {
            $this->info("Dispatched {$stuck->count()} recovery job(s).");
        }

        return self::SUCCESS;
    }
}
