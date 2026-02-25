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

        $installedModules = $tenant->installed_modules ?? [];

        return view('tenant.modules.index', compact('modules', 'requestModules', 'installedModules'));
    }

    public function request(Request $request): RedirectResponse
    {
        $tenant = tenant();

        $data = $request->validate(['module_id' => ['required', 'integer']]);
        $module = Module::whereKey($data['module_id'])->where('is_active', true)->firstOrFail();


        $installedModules = $tenant->installed_modules ?? [];

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
}
