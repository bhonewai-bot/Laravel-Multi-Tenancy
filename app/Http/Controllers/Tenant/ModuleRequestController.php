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
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

/**
 * Handles tenant-side module requests and asynchronous install/uninstall watch state.
 *
 * The controller stores operation state on the central tenant record so the tenant UI
 * can observe background work without querying tenant databases directly.
 */
class ModuleRequestController extends Controller
{
    /**
     * Display available modules together with request and operation state for the tenant.
     */
    public function index(Request $request, TenantModuleRegistry $registry): View
    {
        $this->authorize('viewAny', ModuleRequest::class);

        $tenant = tenant();
        $modules = Module::where('is_active', true)->orderBy('name')->get();
        $requestModules = ModuleRequest::where('tenant_id', $tenant->id)->pluck('status', 'module_id');
        $installedModules = $registry->getInstalledModules($tenant);
        $moduleOperations = $registry->getModuleOperations($tenant);
        [$watching, $watchDone, $operationAlert, $moduleOperations] = $this->resolveWatchState(
            $request,
            $registry,
            $tenant,
            $modules,
            $moduleOperations
        );

        $moduleRows = $this->buildModuleRows(
            $modules,
            $requestModules,
            $installedModules,
            $moduleOperations,
            $registry
        );

        return view('tenant.modules.index', compact(
            'moduleRows',
            'watching',
            'watchDone',
            'operationAlert'
        ));
    }

    /**
     * Create or refresh a module request for the current tenant.
     *
     * Side effects:
     * - Writes to the central module_requests table.
     */
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

    /**
     * Queue an approved module installation for the current tenant.
     *
     * Side effects:
     * - Writes queued state to the central tenant record.
     * - Dispatches a queued install job.
     */
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

        $registry->startModuleOperation(
            $tenant,
            $module->slug,
            TenantModuleRegistry::ACTION_INSTALL,
            "Installing '{$module->name}'..."
        );

        InstallTenantModule::dispatch($tenant->id, $module->id);

        return $this->redirectToWatch($module, TenantModuleRegistry::ACTION_INSTALL);
    }

    /**
     * Queue a module uninstall for the current tenant.
     *
     * Side effects:
     * - Writes queued state to the central tenant record.
     * - Dispatches a queued uninstall job.
     */
    public function uninstall(Request $request, TenantModuleRegistry $registry): RedirectResponse
    {
        $this->authorize('uninstall', ModuleRequest::class);

        $tenant = tenant();

        $data = $request->validate(['module_id' => ['required', 'integer']]);
        $module = Module::whereKey($data['module_id'])->firstOrFail();

        $registry->startModuleOperation(
            $tenant,
            $module->slug,
            TenantModuleRegistry::ACTION_UNINSTALL,
            "Uninstalling '{$module->name}'..."
        );

        UninstallTenantModule::dispatch($tenant->id, $module->id);

        return $this->redirectToWatch($module, TenantModuleRegistry::ACTION_UNINSTALL);
    }

    /**
     * Redirect to the module list with watch parameters so the UI can poll job progress.
     */
    private function redirectToWatch(Module $module, string $action): RedirectResponse
    {
        return redirect()->route('tenant.modules.index', [
            'watch_module_id' => $module->id,
            'watch_action' => $action,
            'watch_attempt' => 0,
        ]);
    }

    /**
     * Resolve the current watch state from query parameters and persisted operation metadata.
     *
     * @param  mixed  $tenant
     */
    private function resolveWatchState(
        Request $request,
        TenantModuleRegistry $registry,
        $tenant,
        Collection $modules,
        array $moduleOperations
    ): array {
        $watchModuleId = (int) $request->query('watch_module_id', 0);
        $watchAction = (string) $request->query('watch_action', '');

        $watching = $watchModuleId > 0
            && in_array($watchAction, [TenantModuleRegistry::ACTION_INSTALL, TenantModuleRegistry::ACTION_UNINSTALL], true);

        if (! $watching) {
            return [false, false, null, $moduleOperations];
        }

        // A missing module should stop the watch loop immediately so the page does not poll forever.
        $watchedModule = $modules->firstWhere('id', $watchModuleId);
        if (! $watchedModule) {
            return [true, true, ['type' => 'error', 'message' => 'Module not found.'], $moduleOperations];
        }

        $operation = $registry->getModuleOperation($tenant, $watchedModule->slug);
        if (! $operation) {
            return [true, true, null, $moduleOperations];
        }

        $status = $operation['status'] ?? null;
        if (! $registry->isTerminalStatus($status)) {
            return [true, false, null, $moduleOperations];
        }

        $alert = [
            'type' => $status === TenantModuleRegistry::OP_STATUS_SUCCESS ? 'success' : 'error',
            'message' => (string) ($operation['message'] ?? 'Module operation completed.'),
        ];

        $registry->clearModuleOperation($tenant, $watchedModule->slug);
        unset($moduleOperations[$watchedModule->slug]);

        return [true, true, $alert, $moduleOperations];
    }

    /**
     * Build the per-module view model consumed by the tenant modules page.
     */
    private function buildModuleRows(
        Collection $modules,
        Collection $requestModules,
        array $installedModules,
        array $moduleOperations,
        TenantModuleRegistry $registry
    ): Collection {
        return $modules->map(function (Module $module) use ($requestModules, $installedModules, $moduleOperations, $registry) {
            $operation = $moduleOperations[$module->slug] ?? null;
            $operationStatus = $operation['status'] ?? null;
            $operationAction = $operation['action'] ?? null;
            $isProcessing = $registry->isProcessingStatus($operationStatus);

            return [
                'module' => $module,
                'request_status' => $requestModules->get($module->id),
                'is_installed' => in_array($module->slug, $installedModules, true),
                'is_processing' => $isProcessing,
                'is_queued_install' => $isProcessing && $operationAction === TenantModuleRegistry::ACTION_INSTALL,
                'is_queued_uninstall' => $isProcessing && $operationAction === TenantModuleRegistry::ACTION_UNINSTALL,
                'open_route_name' => Route::has($module->slug.'.index') ? $module->slug.'.index' : null,
            ];
        });
    }
}
