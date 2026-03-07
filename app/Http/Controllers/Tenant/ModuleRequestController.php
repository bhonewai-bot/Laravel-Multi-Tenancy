<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Jobs\InstallTenantModule;
use App\Jobs\UninstallTenantModule;
use App\Models\Module;
use App\Models\ModuleRequest;
use App\Services\TenantModuleRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ModuleRequestController extends Controller
{
    public function index(Request $request, TenantModuleRegistry $registry): View
    {
        $this->authorize('viewAny', ModuleRequest::class);

        $tenant = tenant();
        $modules = Module::where('is_active', true)->orderBy('name')->get();
        $requestModules = ModuleRequest::where('tenant_id', $tenant->id)->pluck('status', 'module_id');
        $installedModules = $registry->getInstalledModules($tenant);
        $moduleOperations = $registry->getModuleOperations($tenant);

        $watchModuleId = (int) $request->query('watch_module_id', 0);
        $watchAction = (string) $request->query('watch_action', '');
        $watching = in_array($watchAction, ['install', 'uninstall'], true) && $watchModuleId > 0;
        $watchDone = false;
        $operationAlert = null;

        if ($watching) {
            $watchedModule = $modules->firstWhere('id', $watchModuleId);

            if (! $watchedModule) {
                $watchDone = true;
                $operationAlert = [
                    'type' => 'error',
                    'message' => 'Module not found.',
                ];
            } else {
                $operation = $registry->getModuleOperation($tenant, $watchedModule->slug);

                if ($operation) {
                    $status = $operation['status'] ?? null;
                    $watchDone = in_array($status, [
                        TenantModuleRegistry::OP_STATUS_SUCCESS,
                        TenantModuleRegistry::OP_STATUS_FAILED,
                    ], true);

                    if ($watchDone) {
                        $operationAlert = [
                            'type' => $status === TenantModuleRegistry::OP_STATUS_SUCCESS ? 'success' : 'error',
                            'message' => (string) ($operation['message'] ?? 'Module operation completed.'),
                        ];

                        $registry->clearModuleOperation($tenant, $watchedModule->slug);
                        unset($moduleOperations[$watchedModule->slug]);
                    }
                } else {
                    // fallback for old in-flight URLs
                    $isInstalled = in_array($watchedModule->slug, $installedModules, true);
                    $watchDone = $watchAction === 'install' ? $isInstalled : ! $isInstalled;

                    if ($watchDone) {
                        $operationAlert = [
                            'type' => 'success',
                            'message' => $watchAction === 'install'
                                ? "Module '{$watchedModule->name}' installed."
                                : "Module '{$watchedModule->name}' uninstalled.",
                        ];
                    }
                }
            }
        }

        return view('tenant.modules.index', compact(
            'modules',
            'requestModules',
            'installedModules',
            'moduleOperations',
            'watching',
            'watchDone',
            'watchAction',
            'watchModuleId',
            'operationAlert'
        ));
    }

    public function request(Request $request, TenantModuleRegistry $registry): RedirectResponse
    {
        $this->authorize('request', ModuleRequest::class);

        $tenant = tenant();

        $data = $request->validate(['module_id' => ['required', 'integer']]);
        $module = Module::whereKey($data['module_id'])->where('is_active', true)->firstOrFail();

        $installedModules = $registry->getInstalledModules($tenant);

        if (in_array($module->slug, $installedModules, true)) {
            return back()->with('error', 'Module is already installed.');
        }

        $existingRequest = ModuleRequest::where('tenant_id', $tenant->id)
            ->where('module_id', $module->id)
            ->whereIn('status', ['pending', 'approved'])
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

    public function install(Request $request, TenantModuleRegistry $registry): RedirectResponse
    {
        $this->authorize('install', ModuleRequest::class);

        $tenant = tenant();

        $data = $request->validate(['module_id' => ['required', 'integer']]);
        $module = Module::whereKey($data['module_id'])->where('is_active', true)->firstOrFail();

        $isApproved = ModuleRequest::where('tenant_id', $tenant->id)
            ->where('module_id', $module->id)
            ->where('status', 'approved')
            ->exists();

        if (! $isApproved) {
            return back()->with('error', 'Module is not approved yet.');
        }

        $registry->startModuleOperation($tenant, $module->slug, 'install', "Installing '{$module->name}'...");

        InstallTenantModule::dispatch($tenant->id, $module->id);

        return redirect()->route('tenant.modules.index', [
            'watch_module_id' => $module->id,
            'watch_action' => 'install',
            'watch_attempt' => 0,
        ]);
    }

    public function uninstall(Request $request, TenantModuleRegistry $registry): RedirectResponse
    {
        $this->authorize('uninstall', ModuleRequest::class);

        $tenant = tenant();

        $data = $request->validate(['module_id' => ['required', 'integer']]);
        $module = Module::whereKey($data['module_id'])->firstOrFail();

        $registry->startModuleOperation($tenant, $module->slug, 'uninstall', "Uninstalling '{$module->name}'...");

        UninstallTenantModule::dispatch($tenant->id, $module->id);

        return redirect()->route('tenant.modules.index', [
            'watch_module_id' => $module->id,
            'watch_action' => 'uninstall',
            'watch_attempt' => 0,
        ]);
    }
}
