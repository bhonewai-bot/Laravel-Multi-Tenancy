<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserStoreRequest;
use App\Http\Requests\UserUpdateRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $this->authorize('viewAny', User::class);

        $users = User::latest()->paginate(15);

        return view('tenant.users.index', compact('users'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $this->authorize('create', User::class);

        $roles = Role::query()->orderBy('name')->get();

        return view('tenant.users.create', compact('roles'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(UserStoreRequest $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $validated = $request->validated();

        User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role_id' => $validated['role_id'] ?? null
        ]);

        return redirect()->route('tenant.users.index')->with('success', 'User created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user): View
    {
        $this->authorize('view', $user);

        $user->load('role.permissions.feature');

        return view('tenant.users.show', compact('user'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user): View
    {
        $this->authorize('update', $user);

        $user->load('role.permissions.feature');
        $roles = Role::query()->orderBy('name')->get();

        return view('tenant.users.edit', compact('user', 'roles'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UserUpdateRequest $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $validated = $request->validated();

        $payload = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role_id' => $validated['role_id'] ?? null,
        ];

        $isAdminUser = strtolower((string) $user->role?->name) === 'admin';
        $isRoleChanging = (int) ($validated['role_id'] ?? 0) !== (int) ($user->role_id ?? 0);

        if ($isAdminUser && $isRoleChanging) {
            $adminRoleId = Role::query()->where('name', 'admin')->value('id');

            if ($adminRoleId) {
                $adminCount = User::query()->where('role_id', $adminRoleId)->count();

                if ($adminCount <= 1) {
                    return back()->with('error', 'Cannot change role for the last admin user.');
                }
            }
        }

        if (! empty($validated['password'])) {
            $payload['password'] = $validated['password'];
        }

        $user->update($payload);

        return redirect()->route('tenant.users.index')->with('success', 'User updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        if ((int) auth()->id() === (int) $user->id) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $isDeletingAdmin = strtolower((string) $user->role?->name) === 'admin';

        if ($isDeletingAdmin) {
            $adminRoleId = Role::query()->where('name', 'admin')->value('id');

            if ($adminRoleId) {
                $adminCount = User::query()->where('role_id', $adminRoleId)->count();

                if ($adminCount <= 1) {
                    return back()->with('error', 'Cannot delete the last admin user.');
                }
            }
        }

        $user->delete();

        return redirect()->route('tenant.users.index')->with('success', 'User deleted successfully.');
    }
}
