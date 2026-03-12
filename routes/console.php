<?php

use App\Models\Domain;
use App\Services\CloudflareService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('domains:sync-cloudflare {domain : Domain ID or hostname}', function () {
    $domainIdentifier = (string) $this->argument('domain');

    $domain = ctype_digit($domainIdentifier)
        ? Domain::query()->find($domainIdentifier)
        : Domain::query()->where('domain', strtolower($domainIdentifier))->first();

    if (! $domain) {
        $this->error("Domain not found: {$domainIdentifier}");

        return self::FAILURE;
    }

    try {
        $cloudflare = app(CloudflareService::class);

        $cf = $domain->cf_hostname_id
            ? $cloudflare->getHostname($domain->cf_hostname_id)
            : $cloudflare->createHostname($domain->domain);

        $domain->fill($cloudflare->mapStatuses($cf));
        $domain->cf_last_checked_at = now();
        $domain->verified_at = (
            $domain->cf_hostname_status === 'active' &&
            $domain->cf_ssl_status === 'active'
        ) ? now() : null;
        $domain->save();

        $this->table(
            ['domain', 'cf_hostname_id', 'hostname_status', 'ssl_status', 'verified_at', 'cf_error'],
            [[
                $domain->domain,
                $domain->cf_hostname_id,
                $domain->cf_hostname_status,
                $domain->cf_ssl_status,
                optional($domain->verified_at)?->toDateTimeString(),
                $domain->cf_error,
            ]]
        );

        if ($domain->verified_at) {
            $this->info('Domain is active and verified.');
        } else {
            $this->warn('Domain synced, but Cloudflare has not fully activated it yet.');
        }

        return self::SUCCESS;
    } catch (Throwable $e) {
        $domain->update([
            'cf_error' => $e->getMessage(),
            'cf_last_checked_at' => now(),
        ]);

        $this->error($e->getMessage());

        return self::FAILURE;
    }
})->purpose('Create or refresh Cloudflare custom-hostname status for a tenant domain');
