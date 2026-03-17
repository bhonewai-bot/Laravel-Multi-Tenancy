<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Domain;
use App\Services\CloudflareService;
use App\Services\TenantDomainService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Throwable;

/**
 * Manages tenant-owned domains inside the active tenant request context.
 *
 * Tenant isolation is enforced by checking the active tenant against each central
 * domain record before any data is displayed or modified.
 */
class DomainController extends Controller
{
    public function __construct(
        private TenantDomainService $domainService,
        private ?CloudflareService $cloudflareService = null
    ) {}

    /**
     * Display domains belonging to the current tenant.
     *
     * @return View
     */
    public function index(): View
    {
        $tenant = tenant();

        // The tenant_id predicate is the main safeguard against cross-tenant domain leakage.
        $domains = Domain::query()
            ->where('tenant_id', $tenant->id)
            ->orderBy('domain')
            ->get();

        return view('tenant.domains.index', [
            'domains' => $domains,
            'tenant' => $tenant,
            'domainService' => $this->domainService,
        ]);
    }

    /**
     * Show the create-domain form for the current tenant.
     *
     * @return View
     */
    public function create(): View
    {
        return view('tenant.domains.create');
    }

    /**
     * Display a single tenant domain and its DNS guidance.
     *
     * @param  Domain  $domain
     * @return View
     */
    public function show(Domain $domain): View
    {
        $tenant = tenant();

        // WARNING: Route model binding resolves centrally, so ownership must be verified explicitly.
        if ($domain->tenant_id !== $tenant->id) {
            abort(404);
        }

        $fallbackOrigin = (string) config('cloudflare.fallback_origin');
        $cnameName = str_ends_with($domain->domain, '.' . $fallbackOrigin)
            ? '@'
            : explode('.', $domain->domain)[0];

        return view('tenant.domains.show', [
            'domain' => $domain,
            'tenant' => $tenant,
            'domainService' => $this->domainService,
            'fallbackOrigin' => $fallbackOrigin,
            'cnameName' => $cnameName,
        ]);
    }

    /**
     * Persist a new custom domain for the current tenant.
     *
     * Side effects:
     * - Writes to the central domains table.
     * - May call Cloudflare to create a custom hostname.
     *
     * @param  Request  $request
     * @return RedirectResponse
     */
    public function store(Request $request): RedirectResponse
    {
        $tenant = tenant();

        $centralConnection = config('tenancy.database.central_connection', config('database.default'));

        $validator = Validator::make($request->all(), [
            'domain' => ['required', 'string', 'max:255', "unique:{$centralConnection}.domains,domain"],
        ]);

        $validator->after(function ($validator) use ($tenant, $request) {
            // These rules prevent malformed domains and central-domain collisions before persistence.
            $domain = $this->domainService->normalize($request->input('domain', ''));

            if (! $this->isValidDomain($domain)) {
                $validator->errors()->add('domain', 'Enter a valid host (no http://, no path).');
            }

            if ($this->domainService->isCentralDomain($domain)) {
                $validator->errors()->add('domain', 'Central domains cannot be used as custom tenant domains.');
            }

            if ($this->domainService->isPrimarySubDomain($tenant, $domain)) {
                $validator->errors()->add('domain', 'Primary tenant domain is already in use.');
            }
        });

        $data = $validator->validate();
        $host = $this->domainService->normalize($data['domain']);

        $domain = Domain::query()->create([
            'tenant_id' => $tenant->id,
            'domain' => $host,
            'verification_code' => null,
            'verified_at' => null,
        ]);

        if (!config('cloudflare.enabled')) {
            return redirect()->route('tenant.domains.index')
                ->with('warning', "Domain {$host} saved, but Cloudflare integration is disabled.");
        }

        try {
            $this->syncCloudflareForDomain($domain, createWhenMissing: true);

            return redirect()->route('tenant.domains.index')
                ->with('success', "Domain {$host} added. " . $this->statusMessage($domain));
        } catch (Throwable $e) {
            $domain->update([
                'cf_error' => $e->getMessage(),
                'cf_last_checked_at' => now(),
            ]);

            $this->logCloudflareSync('error', 'cloudflare.hostname.create_failed', $domain, [
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            return redirect()->route('tenant.domains.index')
                ->with('error', "Domain saved, but Cloudflare create failed: {$e->getMessage()}");
        }
    }

    /**
     * Refresh Cloudflare activation state for a tenant domain.
     *
     * Side effects:
     * - May call Cloudflare.
     * - Writes Cloudflare status fields to the central domains table.
     *
     * @param  Domain  $domain
     * @return RedirectResponse
     */
    public function checkStatus(Domain $domain): RedirectResponse
    {
        if ($domain->tenant_id !== tenant()->id) {
            abort(404);
        }

        try {
            $this->syncCloudflareForDomain($domain, createWhenMissing: true);

            if ($domain->verified_at) {
                return back()->with('success', "Domain is active and SSL is live.");
            }

            return back()->with('warning', $this->statusMessage($domain));
        } catch (Throwable $e) {
            $domain->update([
                'cf_error' => $e->getMessage(),
                'cf_last_checked_at' => now(),
            ]);

            $this->logCloudflareSync('error', 'cloudflare.hostname.status_check_failed', $domain, [
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            return back()->with('error', "Status check failed: {$e->getMessage()}");
        }
    }

    /**
     * Verify the domain using either Cloudflare polling or legacy TXT records.
     *
     * Side effects:
     * - May call Cloudflare or perform DNS lookups.
     * - Writes verification metadata to the central domains table.
     *
     * @param  Domain  $domain
     * @return RedirectResponse
     */
    public function verify(Domain $domain): RedirectResponse
    {
        // Backward-compatible behavior:
        // - Cloudflare-enabled domains use status polling.
        // - Legacy TXT domains still use DNS TXT verification.
        if ($domain->cf_hostname_id) {
            return $this->checkStatus($domain);
        }

        $tenant = tenant();

        if ($domain->tenant_id !== $tenant->id) {
            abort(404);
        }

        if ($this->domainService->isPrimarySubDomain($tenant, $domain->domain)) {
            return back()->with('success', 'Primary tenant subdomain is trusted automatically.');
        }

        // Generate the TXT token lazily so retries use a stable expected DNS record.
        if (! $domain->verification_code) {
            $domain->verification_code = $this->domainService->makeVerificationCode();
            $domain->verified_at = null;
            $domain->save();
        }

        $ok = $this->domainService->checkDnsTxtVerification($domain->domain, $domain->verification_code);

        if (! $ok) {
            $record = $this->domainService->verificationRecordName($domain->domain);

            return back()->with('error', "DNS TXT not matched. Expected: {$record} = {$domain->verification_code}");
        }

        $domain->verified_at = now();
        $domain->save();

        return back()->with('success', "Domain {$domain->domain} verified successfully.");
    }

    /**
     * Delete a tenant custom domain.
     *
     * Side effects:
     * - Deletes from the central domains table.
     *
     * @param  Domain  $domain
     * @return RedirectResponse
     */
    public function destroy(Domain $domain): RedirectResponse
    {
        $tenant = tenant();

        if ($domain->tenant_id !== $tenant->id) {
            abort(404);
        }

        if ($this->domainService->isPrimarySubDomain($tenant, $domain->domain)) {
            return back()->with('error', 'Primary tenant subdomain cannot be deleted.');
        }

        $domain->delete();

        return redirect()
            ->route('tenant.domains.index')
            ->with('success', 'Custom domain deleted successfully.');
    }

    /**
     * Validate that the submitted value is a hostname rather than a URL.
     *
     * @param  string  $domain
     * @return bool
     */
    private function isValidDomain(string $domain): bool
    {
        if ($domain == '') {
            return false;
        }

        if (str_contains($domain, '://') || str_contains($domain, '/')) {
            return false;
        }

        return filter_var('http://' . $domain, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Create or refresh the Cloudflare hostname backing a tenant domain.
     *
     * Side effects:
     * - Calls Cloudflare.
     * - Writes Cloudflare status fields to the central domains table.
     *
     * @param  Domain  $domain
     * @param  bool  $createWhenMissing
     * @return void
     */
    private function syncCloudflareForDomain(Domain $domain, bool $createWhenMissing = false): void
    {
        $cloudflare = $this->cloudflareService ?? app(CloudflareService::class);
        $action = $domain->cf_hostname_id ? 'refresh' : 'create';

        $this->logCloudflareSync('info', 'cloudflare.hostname.sync_started', $domain, [
            'action' => $action,
            'create_when_missing' => $createWhenMissing,
        ]);

        // Missing hostname ids are only acceptable during the initial create call.
        $cf = $domain->cf_hostname_id
            ? $cloudflare->getHostname($domain->cf_hostname_id)
            : ($createWhenMissing
                ? $cloudflare->createHostname($domain->domain)
                : throw new \RuntimeException('Cloudflare hostname ID is missing.'));

        $domain->fill($cloudflare->mapStatuses($cf));
        $domain->cf_last_checked_at = now();
        $domain->verified_at = $this->shouldMarkVerified($domain) ? now() : null;
        $domain->save();

        $this->logCloudflareSync('info', 'cloudflare.hostname.sync_completed', $domain, [
            'action' => $action,
            'verified_now' => $domain->verified_at !== null,
        ]);
    }

    /**
     * Determine whether the current Cloudflare state is strong enough to trust the domain.
     *
     * @param  Domain  $domain
     * @return bool
     */
    private function shouldMarkVerified(Domain $domain): bool
    {
        return $domain->cf_hostname_status === 'active'
            && $domain->cf_ssl_status === 'active';
    }

    /**
     * Build a human-readable status summary for the tenant UI.
     *
     * @param  Domain  $domain
     * @return string
     */
    private function statusMessage(Domain $domain): string
    {
        $parts = [
            'Hostname: ' . ($domain->cf_hostname_status ?? 'pending'),
            'SSL: ' . ($domain->cf_ssl_status ?? 'pending'),
        ];

        if ($domain->verified_at) {
            $parts[] = 'Verified and ready to serve traffic.';
        } elseif ($domain->cf_error) {
            $parts[] = 'Last Cloudflare error: ' . $domain->cf_error;
        } else {
            $parts[] = 'Still waiting for Cloudflare activation.';
        }

        return implode(' ', $parts);
    }

    /**
     * Emit structured Cloudflare sync logs for operational debugging.
     *
     * @param  string  $level
     * @param  string  $message
     * @param  Domain  $domain
     * @param  array  $context
     * @return void
     */
    private function logCloudflareSync(string $level, string $message, Domain $domain, array $context = []): void
    {
        Log::{$level}($message, array_merge([
            'tenant_id' => $domain->tenant_id,
            'domain' => $domain->domain,
            'cf_hostname_id' => $domain->cf_hostname_id,
            'cf_hostname_status' => $domain->cf_hostname_status,
            'cf_ssl_status' => $domain->cf_ssl_status,
            'verified_at' => optional($domain->verified_at)?->toIso8601String(),
            'cf_last_checked_at' => optional($domain->cf_last_checked_at)?->toIso8601String(),
            'cf_error' => $domain->cf_error,
        ], $context));
    }
}
