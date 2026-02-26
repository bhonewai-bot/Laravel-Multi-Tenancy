<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Models\ModuleRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ModuleRequestController extends Controller
{
    public function index(): View
    {
        $tenant = tenant();

        $modules = Module::where('is_active', true)->orderBy('name')->get();

        $requestModules = ModuleRequest::where('tenant_id', $tenant->id)
            ->pluck('status', 'module_id');

        $installedModules = $this->getInstalledModules($tenant);

        return view('tenant.modules.index', compact('modules', 'requestModules', 'installedModules'));
    }

    public function request(Request $request): RedirectResponse
    {
        $tenant = tenant();

        $data = $request->validate(['module_id' => ['required', 'integer']]);
        $module = Module::whereKey($data['module_id'])->where('is_active', true)->firstOrFail();

        $installedModules = $this->getInstalledModules($tenant);

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

    public function install(Request $request): RedirectResponse
    {
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

        $installedModules = $this->getInstalledModules($tenant);

        if (in_array($module->slug, $installedModules, true)) {
            return back()->with('error', 'Module is already installed.');
        }

        $installedModules[] = $module->slug;
        $this->saveInstalledModules($tenant, $installedModules);

        return back()->with('success', "Module '{$module->name}' installed.");
    }

    public function uninstall(Request $request): RedirectResponse
    {
        $tenant = tenant();

        $data = $request->validate(['module_id' => ['required', 'integer']]);
        $module = Module::whereKey($data['module_id'])->firstOrFail();

        $installedModules = $this->getInstalledModules($tenant);

        if (!in_array($module->slug, $installedModules, true)) {
            return back()->with('error', 'Module is not installed.');
        }

        unset($installedModules[array_search($module->slug, $installedModules, true)]);
        $this->saveInstalledModules($tenant, $installedModules);

        return back()->with('success', "Module '{$module->name}' uninstalled.");
    }

    private function getInstalledModules($tenant): array
    {
        $installedModules = $tenant->getAttribute('installed_modules') ?? [];

        if (!is_array($installedModules)) {
            return [];
        }

        return $installedModules;
    }

    private function saveInstalledModules($tenant, array $installedModules): void
    {
        $tenant->setAttribute('installed_modules', $installedModules);
        $tenant->save();
    }
}
