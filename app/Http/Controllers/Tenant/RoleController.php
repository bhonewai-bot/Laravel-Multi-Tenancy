<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\RoleStoreRequest;
use App\Http\Requests\RoleUpdateRequest;
use App\Models\Feature;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $this->authorize('viewAny', Role::class);

        $roles = Role::query()
            ->withCount('users')
            ->with('permissions.feature')
            ->latest()
            ->paginate(15);

        return view('tenant.roles.index', compact('roles'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $this->authorize('create', Role::class);

        $features = Feature::query()->with('permissions')->orderBy('name')->get();

        return view('tenant.roles.create', compact('features'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(RoleStoreRequest $request): RedirectResponse
    {
        $this->authorize('create', Role::class);

        $validated = $request->validated();

        $role = Role::query()->create([
            'name' => strtolower($validated['name']),
        ]);

        $role->permissions()->sync($validated['permission_ids'] ?? []);

        return redirect()->route('tenant.roles.index')->with('success', 'Role created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Role $role): View
    {
        $this->authorize('view', $role);

        $role->load('permissions.feature', 'users');

        return view('tenant.roles.show', compact('role'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Role $role): View
    {
        $this->authorize('update', $role);

        $role->load('permissions');
        $features = Feature::query()->with('permissions')->orderBy('name')->get();

        return view('tenant.roles.edit', compact('role', 'features'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(RoleUpdateRequest $request, Role $role): RedirectResponse
    {
        $this->authorize('update', $role);

        $validated = $request->validated();

        $role->update([
            'name' => strtolower($validated['name']),
        ]);

        $role->permissions()->sync($validated['permission_ids'] ?? []);

        return redirect()->route('tenant.roles.index')->with('success', 'Role updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Role $role): RedirectResponse
    {
        $this->authorize('delete', $role);

        if ($role->users()->exists()) {
            return back()->with('error', 'Cannot delete role assigned to users.');
        }

        if (in_array(strtolower($role->name), ['admin', 'staff'], true)) {
            return back()->with('error', 'System roles cannot be deleted.');
        }

        $role->delete();

        return redirect()->route('tenant.roles.index')->with('success', 'Role deleted successfully.');
    }
}
