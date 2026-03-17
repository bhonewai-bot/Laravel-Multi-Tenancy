<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\RoleStoreRequest;
use App\Http\Requests\RoleUpdateRequest;
use App\Models\Feature;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Manages tenant-scoped roles and their permission assignments.
 */
class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return View
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
     *
     * @return View
     */
    public function create(): View
    {
        $this->authorize('create', Role::class);

        $features = Feature::query()->with('permissions')->orderBy('name')->get();

        return view('tenant.roles.create', compact('features'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * Side effects:
     * - Writes to the tenant roles table.
     * - Syncs permissions in the tenant database.
     *
     * @param  RoleStoreRequest  $request
     * @return RedirectResponse
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
     *
     * @param  Role  $role
     * @return View
     */
    public function show(Role $role): View
    {
        $this->authorize('view', $role);

        $role->load('permissions.feature', 'users');

        return view('tenant.roles.show', compact('role'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  Role  $role
     * @return View
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
     *
     * Side effects:
     * - Writes to the tenant roles table.
     * - Syncs permissions in the tenant database.
     *
     * @param  RoleUpdateRequest  $request
     * @param  Role  $role
     * @return RedirectResponse
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
     *
     * Side effects:
     * - Deletes a tenant role when business rules allow it.
     *
     * @param  Role  $role
     * @return RedirectResponse
     */
    public function destroy(Role $role): RedirectResponse
    {
        $this->authorize('delete', $role);

        if ($role->users()->exists()) {
            return back()->with('error', 'Cannot delete role assigned to users.');
        }

        // System roles define baseline tenant authorization and are intentionally protected from deletion.
        if (in_array(strtolower($role->name), ['admin', 'staff'], true)) {
            return back()->with('error', 'System roles cannot be deleted.');
        }

        $role->delete();

        return redirect()->route('tenant.roles.index')->with('success', 'Role deleted successfully.');
    }
}
