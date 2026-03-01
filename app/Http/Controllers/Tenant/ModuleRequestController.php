<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Models\ModuleRequest;
use App\Services\TenantModuleInstaller;
use App\Services\TenantModuleRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class ModuleRequestController extends Controller
{
    public function index(TenantModuleRegistry $registry): View
    {
        $this->authorize('viewAny', ModuleRequest::class);

        $tenant = tenant();

        $modules = Module::where('is_active', true)->orderBy('name')->get();

        $requestModules = ModuleRequest::where('tenant_id', $tenant->id)
            ->pluck('status', 'module_id');

        $installedModules = $registry->getInstalledModules($tenant);

        return view('tenant.modules.index', compact('modules', 'requestModules', 'installedModules'));
    }

    public function request(Request $request, TenantModuleRegistry $registry): RedirectResponse
    {
        $this->authorize('request', ModuleRequest::class);

        $tenant = tenant();

        $data = $request->validate(['module_id' => ['required', 'integer']]);
        $module = Module::whereKey($data['module_id'])->where('is_active', true)->firstOrFail();

        $installedModules = $registry->getInstalledModules($tenant);

        // Check if already installed
        if (in_array($module->slug, $installedModules, true)) {
            return back()->with('error', 'Module is already installed.');
        }

        // Check if already requesteds
        $existingRequest = ModuleRequest::where('tenant_id', $tenant->id)
            ->where('module_id', $module->id)
            ->whereIn('status', ['pending', 'approved']) // Include approved in check to prevent duplicate requests
            ->first();

        if ($existingRequest) {
            return back()->with('error', 'Module is already requested.');
        }

        ModuleRequest::updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'module_id' => $module->id,
            ],
            [
                'status' => 'pending',
                'reviewed_at' => null,
                'review_note' => null,
            ]
        );

        return back()->with('success', 'Module request sent.');
    }  

    public function install(Request $request, TenantModuleInstaller $installer): RedirectResponse
    {
        $this->authorize('install', ModuleRequest::class);

        $tenant = tenant();

        $data = $request->validate(['module_id' => ['required', 'integer']]);
        $module = Module::whereKey($data['module_id'])->where('is_active', true)->firstOrFail();

        $isApproved =ModuleRequest::where('tenant_id', $tenant->id)
            ->where('module_id', $module->id)
            ->where('status', 'approved')
            ->exists();

        if (!$isApproved) {
            return back()->with('error', 'Module is not approved yet.');
        }

        try {
            $installer->install($tenant, $module);
        } catch (Throwable $e) {
            report($e);
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Module '{$module->name}' installed.");
    }

    public function uninstall(Request $request, TenantModuleInstaller $installer, TenantModuleRegistry $registry): RedirectResponse
    {
        $this->authorize('uninstall', ModuleRequest::class);

        $tenant = tenant();

        $data = $request->validate(['module_id' => ['required', 'integer']]);
        $module = Module::whereKey($data['module_id'])->firstOrFail();

        if (!in_array($module->slug, $registry->getInstalledModules($tenant), true)) {
            return back()->with('error', 'Module is not installed.');
        }

        try {
            $installer->uninstall($tenant, $module);
        } catch (Throwable $e) {
            report($e);
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Module '{$module->name}' uninstalled.");
    }
}
