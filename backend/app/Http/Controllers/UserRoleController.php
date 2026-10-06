<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserRoleController extends Controller
{
    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()?->role === 'admin', 403, 'Administrator access required.');
    }

    public function users(Request $request)
    {
        $this->authorizeAdmin($request);

        $permissionCounts = DB::table('role_permissions')
            ->select('role', DB::raw('COUNT(*) as total'))
            ->groupBy('role')
            ->pluck('total', 'role');

        $data = User::query()->orderBy('name')->get(['id', 'name', 'email', 'role', 'created_at'])
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'role_label' => $this->roleLabel($user->role),
                'permissions_count' => $user->role === 'admin'
                    ? Permission::count()
                    : (int) ($permissionCounts[$user->role] ?? 0),
                'active' => true,
                'created_at' => $user->created_at?->toDateString(),
            ]);

        return response()->json(['data' => $data]);
    }

    public function store(Request $request)
    {
        $this->authorizeAdmin($request);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', Rule::in(User::ROLES)],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
        ]);

        return response()->json(['message' => 'User created successfully.', 'data' => $user], 201);
    }

    public function update(Request $request, User $user)
    {
        $this->authorizeAdmin($request);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', Rule::in(User::ROLES)],
            'password' => ['nullable', 'string', 'min:8'],
        ]);

        if ($user->id === $request->user()->id && $data['role'] !== 'admin') {
            return response()->json(['message' => 'You cannot remove the administrator role from your own account.'], 422);
        }

        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->role = $data['role'];
        if (!empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }
        $user->save();

        return response()->json(['message' => 'User updated successfully.', 'data' => $user]);
    }

    public function destroy(Request $request, User $user)
    {
        $this->authorizeAdmin($request);

        if ($user->id === $request->user()->id) {
            return response()->json(['message' => 'You cannot delete your own administrator account.'], 422);
        }

        $user->tokens()->delete();
        $user->delete();

        return response()->json(['message' => 'User deleted successfully.']);
    }

    public function roles(Request $request)
    {
        $this->authorizeAdmin($request);

        $permissions = Permission::query()->orderBy('group')->orderBy('name')->get(['id', 'name', 'label', 'group']);
        $rolePermissions = DB::table('role_permissions')
            ->join('permissions', 'permissions.id', '=', 'role_permissions.permission_id')
            ->select('role_permissions.role', 'permissions.name')
            ->get()
            ->groupBy('role')
            ->map(fn ($items) => $items->pluck('name')->values()->all());

        $roles = collect(User::ROLES)->map(fn ($role) => [
            'key' => $role,
            'label' => $this->roleLabel($role),
            'description' => $this->roleDescription($role),
            'permissions' => $role === 'admin'
                ? $permissions->pluck('name')->values()->all()
                : ($rolePermissions[$role] ?? []),
        ])->values();

        return response()->json([
            'roles' => $roles,
            'permissions' => $permissions,
        ]);
    }

    public function updateRolePermissions(Request $request, string $role)
    {
        $this->authorizeAdmin($request);

        abort_unless(in_array($role, User::ROLES, true), 404, 'Role not found.');

        if ($role === 'admin') {
            return response()->json(['message' => 'Administrator always has full access and cannot be restricted.'], 422);
        }

        $data = $request->validate([
            'permissions' => ['array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        DB::transaction(function () use ($role, $data) {
            RolePermission::query()->where('role', $role)->delete();
            $permissionIds = Permission::query()->whereIn('name', $data['permissions'] ?? [])->pluck('id');
            foreach ($permissionIds as $permissionId) {
                RolePermission::create(['role' => $role, 'permission_id' => $permissionId]);
            }
        });

        return response()->json(['message' => $this->roleLabel($role) . ' permissions updated successfully.']);
    }

    private function roleLabel(string $role): string
    {
        return [
            'admin' => 'Administrator',
            'project_manager' => 'Project Manager',
            'engineer' => 'Site Engineer',
            'qs' => 'QS Team',
            'viewer' => 'Read Only User',
        ][$role] ?? $role;
    }

    private function roleDescription(string $role): string
    {
        return [
            'admin' => 'Full system access',
            'project_manager' => 'Full project-control access',
            'engineer' => 'Configuration editing and daily plan creation',
            'qs' => 'Configuration management and plan viewing',
            'viewer' => 'Read-only project access',
        ][$role] ?? '';
    }
}
