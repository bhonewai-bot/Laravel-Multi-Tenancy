<?php

namespace App\Http\Controllers;

use App\Models\Module;
use App\Models\ModuleRequest;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantModuleRegistry;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        if (tenant()) {
            return $this->tenantDashboard();
        }

        return $this->centralDashboard();
    }

    private function centralDashboard(): \Illuminate\View\View
    {
        $totalTenants = Tenant::count();
        $totalModules = Module::count();
        $pendingRequests = ModuleRequest::where('status', 'pending')->count();
        $totalUsers = User::count();

        $recentTenants = Tenant::latest()->take(5)->get();
        $recentRequests = ModuleRequest::with(['tenant', 'module'])
            ->latest()
            ->take(5)
            ->get();

        return view('dashboard', compact(
            'totalTenants',
            'totalModules',
            'pendingRequests',
            'totalUsers',
            'recentTenants',
            'recentRequests',
        ));
    }

    private function tenantDashboard(): \Illuminate\View\View
    {
        $tenant = tenant();
        $registry = app(TenantModuleRegistry::class);
        $teamMembers = User::count();
        $installedModules = count($registry->getInstalledModules($tenant));
        $totalDomains = $tenant->domains()->count();
        $pendingRequests = ModuleRequest::where('tenant_id', $tenant->id)
            ->where('status', 'pending')
            ->count();

        $recentRequests = ModuleRequest::with('module')
            ->where('tenant_id', $tenant->id)
            ->latest()
            ->take(5)
            ->get();

        return view('dashboard', compact(
            'tenant',
            'teamMembers',
            'installedModules',
            'totalDomains',
            'pendingRequests',
            'recentRequests',
        ));
    }
}
