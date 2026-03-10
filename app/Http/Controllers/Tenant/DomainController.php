<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Domain;
use App\Services\CloudflareService;
use App\Services\TenantDomainService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Throwable;

class DomainController extends Controller
{
    public function __construct(
        private TenantDomainService $domainService,
        private ?CloudflareService $cloudflareService = null
    ) {}

    public function index(): View
    {
        $tenant = tenant();

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

    public function create(): View
    {
        return view('tenant.domains.create');
    }

    public function show(Domain $domain): View
    {
        $tenant = tenant();

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

    public function store(Request $request): RedirectResponse
    {
        $tenant = tenant();

        $centralConnection = config('tenancy.database.central_connection', config('database.default'));

        $validator = Validator::make($request->all(), [
            'domain' => ['required', 'string', 'max:255', "unique:{$centralConnection}.domains,domain"],
        ]);

        $validator->after(function ($validator) use ($tenant, $request) {
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
            $cloudflare = $this->cloudflareService ?? app(CloudflareService::class);

            $cf = $cloudflare->createHostname($host);
            $status = $cloudflare->mapStatuses($cf);

            $domain->fill($status);
            $domain->cf_last_checked_at = now();
            $domain->verified_at = (
                $domain->cf_hostname_status === 'active' &&
                $domain->cf_ssl_status === 'active'
            ) ? now() : null;
            $domain->save();

            return redirect()->route('tenant.domains.index')
                ->with('success', "Domain {$host} added. Configure CNAME then check status.");
        } catch (Throwable $e) {
            $domain->update([
                'cf_error' => $e->getMessage(),
                'cf_last_checked_at' => now(),
            ]);

            return redirect()->route('tenant.domains.index')
                ->with('error', "Domain saved, but Cloudflare create failed: {$e->getMessage()}");
        }
    }

    public function checkStatus(Domain $domain): RedirectResponse
    {
        if ($domain->tenant_id !== tenant()->id) {
            abort(404);
        }

        if (!$domain->cf_hostname_id) {
            return back()->with('error', 'Missing Cloudflare hostname Id.');
        }

        try {
            $cloudflare = $this->cloudflareService ?? app(CloudflareService::class);

            $cf = $cloudflare->getHostname($domain->cf_hostname_id);
            $status = $cloudflare->mapStatuses($cf);

            $domain->fill($status);
            $domain->cf_last_checked_at = now();
            $domain->verified_at = (
                $domain->cf_hostname_status === 'active' &&
                $domain->cf_ssl_status === 'active'
            ) ? now() : null;
            $domain->save();

            if ($domain->verified_at) {
                return back()->with('success', "Domain is active and SSL is live.");
            }

            return back()->with('warning', "Hostname: {$domain->cf_hostname_status}, SSL: {$domain->cf_ssl_status}.");
        } catch (Throwable $e) {
            $domain->update([
                'cf_error' => $e->getMessage(),
                'cf_last_checked_at' => now(),
            ]);

            return back()->with('error', "Status check failed: {$e->getMessage()}");
        }
    }

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
}
