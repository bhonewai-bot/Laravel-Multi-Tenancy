<?php

namespace App\Http\Controllers\Tenant;

use App\Actions\Tenants\StoreDomainAction;
use App\Actions\Tenants\VerifyDomainAction;
use App\Http\Controllers\Controller;
use App\Models\Domain;
use App\Services\TenantDomainService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use RuntimeException;

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
        private StoreDomainAction $storeDomainAction,
        private VerifyDomainAction $verifyDomainAction,
    ) {}

    /**
     * Display domains belonging to the current tenant.
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
     */
    public function create(): View
    {
        return view('tenant.domains.create');
    }

    /**
     * Display a single tenant domain and its DNS guidance.
     */
    public function show(Domain $domain): View
    {
        $tenant = tenant();

        // WARNING: Route model binding resolves centrally, so ownership must be verified explicitly.
        if ($domain->tenant_id !== $tenant->id) {
            abort(404);
        }

        $fallbackOrigin = (string) config('cloudflare.fallback_origin');
        $cnameName = str_ends_with($domain->domain, '.'.$fallbackOrigin)
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
     */
    public function store(Request $request): RedirectResponse
    {
        $tenant = tenant();

        $data = Validator::make($request->all(), [
            'domain' => ['required', 'string', 'max:255'],
        ])->validate();

        try {
            $result = $this->storeDomainAction->execute($tenant, $data['domain']);
            $flashKey = str_contains($result['message'], 'disabled') ? 'warning' : 'success';

            return redirect()->route('tenant.domains.index')
                ->with($flashKey, $result['message']);
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Refresh Cloudflare activation state for a tenant domain.
     *
     * Side effects:
     * - May call Cloudflare.
     * - Writes Cloudflare status fields to the central domains table.
     */
    public function checkStatus(Domain $domain): RedirectResponse
    {
        if ($domain->tenant_id !== tenant()->id) {
            abort(404);
        }

        $result = $this->verifyDomainAction->execute(tenant(), $domain);

        return back()->with($result['status'] === 'error' ? 'error' : $result['status'], $result['message']);
    }

    /**
     * Verify the domain using either Cloudflare polling or legacy TXT records.
     *
     * Side effects:
     * - May call Cloudflare or perform DNS lookups.
     * - Writes verification metadata to the central domains table.
     */
    public function verify(Domain $domain): RedirectResponse
    {
        $result = $this->verifyDomainAction->execute(tenant(), $domain);

        return back()->with($result['status'] === 'error' ? 'error' : $result['status'], $result['message']);
    }

    /**
     * Delete a tenant custom domain.
     *
     * Side effects:
     * - Deletes from the central domains table.
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
}
