<?php

namespace App\Logging;

use Illuminate\Log\Logger;
use Illuminate\Support\Facades\App;
use Monolog\LogRecord;

class AddTenantContext
{
    public function __invoke(Logger $logger): void
    {
        $logger->getLogger()->pushProcessor(function (array|LogRecord $record) {
            $tenantId = null;

            if (function_exists('tenant')) {
                try {
                    $tenant = tenant();
                    $tenantId = $tenant?->getTenantKey() ?? $tenant?->id ?? null;
                } catch (\Throwable $th) {
                    $tenantId = null;
                }
            }

            $requestId = null;
            $host = null;

            if (App::bound('request')) {
                $request = request();
                $host = $request->getHost();
                $requestId = $request->headers->get('x-request-id')
                    ?? $request->headers->get('x-correlation-id')
                    ?? $request->attributes->get('request_id');
            }

            $jobId = null;
            if (App::bound('queue.job')) {
                $job = App::make('queue.job');
                if (is_object($job) && method_exists($job, 'getJobId')) {
                    $jobId = $job->getJobId();
                }
            }

            $extra = [
                'tenant_id' => $tenantId,
                'host' => $host,
                'context' => $tenantId ? 'tenant' : 'central',
                'request_id' => $requestId,
                'job_id' => $jobId,
            ];

            if ($record instanceof LogRecord) {
                return $record->with(extra: array_merge($record->extra, $extra));
            }

            $record['extra'] = array_merge($record['extra'], $extra);
            return $record;
        });
    }
}
