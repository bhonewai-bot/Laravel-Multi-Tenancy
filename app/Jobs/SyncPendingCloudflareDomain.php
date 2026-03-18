<?php

namespace App\Jobs;

use App\Models\Domain;
use App\Services\DomainCloudflareSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * Re-polls Cloudflare activation state for a pending tenant custom domain.
 *
 * Cloudflare hostname and certificate issuance are asynchronous, so the app
 * queues delayed follow-up checks until the domain becomes trusted or the retry
 * budget is exhausted.
 */
class SyncPendingCloudflareDomain implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const MAX_ATTEMPTS = 15;
    private const RETRY_DELAY_SECONDS = 120;

    public int $tries = 3;
    public int $timeout = 120;

    public function __construct(
        public int $domainId,
        public int $pollAttempt = 1
    ) {}

    /**
     * Refresh the domain's Cloudflare state and requeue if activation is still pending.
     *
     * Side effects:
     * - Reads and writes the central domains table.
     * - Calls Cloudflare.
     * - Dispatches a follow-up queue job while activation is still in progress.
     *
     * @param  DomainCloudflareSyncService  $syncService
     * @return void
     */
    public function handle(DomainCloudflareSyncService $syncService): void
    {
        $domain = Domain::query()->find($this->domainId);

        if (! $domain || $domain->verified_at) {
            return;
        }

        $syncService->sync($domain);

        // Cloudflare activation can lag behind the initial create call, so pending domains are
        // re-polled in the background until either activation completes or the retry budget ends.
        if ($this->pollAttempt < self::MAX_ATTEMPTS && $syncService->shouldRetry($domain)) {
            static::dispatch($domain->id, $this->pollAttempt + 1)
                ->delay(now()->addSeconds(self::RETRY_DELAY_SECONDS));
        }
    }

    /**
     * Capture a terminal queue failure on the central domain record for later troubleshooting.
     *
     * Side effects:
     * - Writes failure metadata to the central domains table.
     * - Emits an error log.
     *
     * @param  Throwable  $exception
     * @return void
     */
    public function failed(Throwable $exception): void
    {
        $domain = Domain::query()->find($this->domainId);

        if ($domain && ! $domain->verified_at) {
            $domain->update([
                'cf_error' => $exception->getMessage(),
                'cf_last_checked_at' => now(),
            ]);
        }

        logger()->error('SyncPendingCloudflareDomain failed.', [
            'domain_id' => $this->domainId,
            'poll_attempt' => $this->pollAttempt,
            'job' => static::class,
            'error' => $exception->getMessage(),
        ]);
    }
}
