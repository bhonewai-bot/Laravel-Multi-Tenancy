<?php

namespace App\Http\Controllers;

use App\Http\Requests\TenantStoreRequest;
use App\Http\Requests\TenantUpdateRequest;
use App\Models\Domain;
use App\Models\Tenant;
use App\Services\CloudflareService;
use App\Services\TenantDomainService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Throwable;

/**
 * Manages tenant provisioning from the central administration surface.
 *
 * Tenant records are stored centrally and then used by the tenancy layer to isolate
 * database, filesystem, and request state per tenant.
 */
class TenantController extends Controller
{
    public function __construct(
        private TenantDomainService $domainService,
        private ?CloudflareService $cloudflareService = null
    ) {}

    /**
     * Display a listing of the resource.
     *
     * @return View
     */
    public function index(): View
    {
        $tenants = Tenant::with('domains')->paginate(15);

        return view('tenant.index', compact('tenants'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return View
     */
    public function create(): View
    {
        return view('tenant.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * Side effects:
     * - Writes tenant and domain records to the central database.
     * - May call Cloudflare to provision domain state.
     *
     * @param  TenantStoreRequest  $request
     * @return RedirectResponse
     */
    public function store(TenantStoreRequest $request): RedirectResponse
    {
        $domain = $this->domainService->normalize((string) $request->input('domain'));

        // Tenant creation starts in the central database; tenant-specific resources are provisioned elsewhere.
        $tenant = Tenant::create([
            'id' => $request->tenant_id,
            'name' => $request->name,
            'email' => $request->email,
            'description' => $request->description,
        ]);

        $domainModel = $tenant->domains()->create([
            'domain' => $domain,
        ]);

        $this->syncCloudflareForDomain($tenant, $domainModel);

        return redirect()
            ->route('tenants.index')
            ->with('success', 'Tenant created successfully.');
            /* ->with('onboarding_credentials', [
                'tenant_id' => $tenant->id,
                'domain' => $request->domain,
                'admin_email' => "admin@{$tenant->id}.local",
                'password_source' => 'TENANT_DEFAULT_ADMIN_PASSWORD',
            ]); */
    }

    /**
     * Display the specified resource.
     *
     * @param  Tenant  $tenant
     * @return View
     */
    public function show(Tenant $tenant): View
    {
        $tenant->load('domains');

        return view('tenant.show', compact('tenant'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  Tenant  $tenant
     * @return View
     */
    public function edit(Tenant $tenant): View
    {
        $tenant->load('domains');

        return view('tenant.edit', compact('tenant'));
    }

    /**
     * Update the specified resource in storage.
     *
     * Side effects:
     * - Writes tenant and domain changes to the central database.
     * - May call Cloudflare to refresh hostname state.
     *
     * @param  TenantUpdateRequest  $request
     * @param  Tenant  $tenant
     * @return RedirectResponse
     */
    public function update(TenantUpdateRequest $request, Tenant $tenant)
    {
        $domain = $this->domainService->normalize((string) $request->input('domain'));
        $domainModel = null;

        DB::transaction(function () use ($request, $tenant) {
            // The core tenant update is transactional so the central record is not left partially updated.
            $tenant->update([
                'name' => $request->name,
                'email' => $request->email,
                'description' => $request->description,
            ]);
        });

        $domainModel = $tenant->domains()->first();

        if ($domainModel) {
            $domainModel->update(['domain' => $domain]);
        } else {
            $domainModel = $tenant->domains()->create(['domain' => $domain]);
        }

        $this->syncCloudflareForDomain($tenant, $domainModel);

        return redirect()->route('tenants.index')->with('success', 'Tenant updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * Side effects:
     * - Deletes the central tenant record.
     * - Triggers downstream tenancy cleanup listeners.
     *
     * @param  Tenant  $tenant
     * @return RedirectResponse
     */
    public function destroy(Tenant $tenant): RedirectResponse
    {
        $tenant->delete();

        return redirect()->route('tenants.index')->with('success', 'Tenant deleted successfully.');
    }

    /**
     * Synchronize central domain metadata with Cloudflare hostname state.
     *
     * Side effects:
     * - May call Cloudflare.
     * - Writes Cloudflare status fields to the central domains table.
     *
     * @param  Tenant  $tenant
     * @param  Domain  $domain
     * @return void
     */
    private function syncCloudflareForDomain(Tenant $tenant, Domain $domain): void
    {
        if (
            ! config('cloudflare.enabled') ||
            $this->domainService->isPrimarySubDomain($tenant, $domain->domain)
        ) {
            // Platform-managed subdomains are trusted by convention and do not need custom-hostname status.
            $domain->forceFill([
                'cf_hostname_id' => null,
                'cf_hostname_status' => null,
                'cf_ssl_status' => null,
                'cf_last_checked_at' => null,
                'cf_error' => null,
                'cf_payload' => null,
                'verified_at' => null,
            ])->save();

            return;
        }

        try {
            $cloudflare = $this->cloudflareService ?? app(CloudflareService::class);
            $cf = $cloudflare->createHostname($domain->domain);
            $status = $cloudflare->mapStatuses($cf);

            $domain->fill($status);
            $domain->cf_last_checked_at = now();
            $domain->verified_at = (
                $domain->cf_hostname_status === 'active' &&
                $domain->cf_ssl_status === 'active'
            ) ? now() : null;
            $domain->save();
        } catch (Throwable $e) {
            // Domain failures are kept as recoverable metadata so operators can retry without recreating the tenant.
            $domain->forceFill([
                'cf_last_checked_at' => now(),
                'cf_error' => $e->getMessage(),
                'verified_at' => null,
            ])->save();
        }
    }
}
