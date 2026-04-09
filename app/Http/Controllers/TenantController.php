<?php

namespace App\Http\Controllers;

use App\Actions\Tenants\CreateTenantAction;
use App\Actions\Tenants\UpdateTenantAction;
use App\Http\Requests\TenantStoreRequest;
use App\Http\Requests\TenantUpdateRequest;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Manages tenant provisioning from the central administration surface.
 *
 * Tenant records are stored centrally and then used by the tenancy layer to isolate
 * database, filesystem, and request state per tenant.
 */
class TenantController extends Controller
{
    public function __construct(
        private CreateTenantAction $createTenant,
        private UpdateTenantAction $updateTenant,
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
        $tenant = $this->createTenant->execute($request->validated());

        return redirect()
            ->route('tenants.index')
            ->with('success', 'Tenant created successfully.');
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
    public function update(TenantUpdateRequest $request, Tenant $tenant): RedirectResponse
    {
        $tenant = $this->updateTenant->execute($request->validated(), $tenant);

        return redirect()
            ->route('tenants.index')
            ->with('success', 'Tenant updated successfully.');
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
}
