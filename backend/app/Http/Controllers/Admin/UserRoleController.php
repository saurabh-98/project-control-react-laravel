<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserRoleController extends Controller
{
    public function users()
    {
        $users = User::query()
            ->orderBy('name')
            ->get()
            ->map(function (User $user) {
                $permissions = $this->permissionNames($user->role);

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'role_label' => Role::where('name', $user->role)->value('label') ?? $user->role,
                    'permission_count' => count($permissions),
                    'created_at' => $user->created_at,
                ];
            });

        return response()->json(['data' => $users]);
    }

    public function roles()
    {
        $roles = Role::query()
            ->orderBy('is_system', 'desc')
            ->orderBy('label')
            ->get()
            ->map(function (Role $role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'label' => $role->label,
                    'is_system' => $role->is_system,
                    'permissions' => $this->permissionNames($role->name),
                ];
            });

        return response()->json([
            'roles' => $roles,
            'permissions' => Permission::query()
                ->orderBy('group')
                ->orderBy('label')
                ->get(['id', 'name', 'label', 'group']),
        ]);
    }

    public function storeUser(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'string', 'exists:roles,name'],
        ]);

        $user = User::create([
            'name' => trim($data['name']),
            'email' => strtolower(trim($data['email'])),
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
        ]);

        return response()->json([
            'message' => 'User created successfully.',
            'data' => $user,
        ], 201);
    }

    public function updateUser(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => [
                'required',
                'email',
                'max:190',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'password' => ['nullable', 'string', 'min:8'],
            'role' => ['required', 'string', 'exists:roles,name'],
        ]);

        if ($user->id === $request->user()->id && $data['role'] !== 'admin') {
            abort(422, 'You cannot remove the Administrator role from your own account.');
        }

        $user->name = trim($data['name']);
        $user->email = strtolower(trim($data['email']));
        $user->role = $data['role'];

        if (!empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();

        return response()->json([
            'message' => 'User updated successfully.',
            'data' => $user,
        ]);
    }

    public function deleteUser(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            abort(422, 'You cannot delete your own account.');
        }

        if ($user->role === 'admin') {
            abort(422, 'Administrator accounts cannot be deleted from this screen.');
        }

        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully.',
        ]);
    }

    public function storeRole(Request $request)
    {
        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-z][a-z0-9_\-]*$/',
                'unique:roles,name',
            ],
            'label' => ['required', 'string', 'max:150'],
            'permission_names' => ['array'],
            'permission_names.*' => ['string', 'exists:permissions,name'],
        ]);

        if ($data['name'] === 'admin') {
            abort(422, 'The Administrator role is reserved.');
        }

        $role = DB::transaction(function () use ($data) {
            $role = Role::create([
                'name' => $data['name'],
                'label' => trim($data['label']),
                'is_system' => false,
            ]);

            $this->syncPermissions($role->name, $data['permission_names'] ?? []);

            return $role;
        });

        return response()->json([
            'message' => 'Role created successfully.',
            'data' => $role,
        ], 201);
    }

    public function updateRole(Request $request, Role $role)
    {
        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-z][a-z0-9_\-]*$/',
                Rule::unique('roles', 'name')->ignore($role->id),
            ],
            'label' => ['required', 'string', 'max:150'],
            'permission_names' => ['array'],
            'permission_names.*' => ['string', 'exists:permissions,name'],
        ]);

        if ($role->name === 'admin') {
            abort(422, 'The Administrator role is protected.');
        }

        if ($role->is_system && $data['name'] !== $role->name) {
            abort(422, 'System role names cannot be changed.');
        }

        DB::transaction(function () use ($role, $data) {
            $oldName = $role->name;

            $role->update([
                'name' => $oldName,
                'label' => trim($data['label']),
            ]);

            $this->syncPermissions(
                $role->name,
                $data['permission_names'] ?? []
            );
        });

        return response()->json([
            'message' => 'Role updated successfully.',
        ]);
    }

    public function deleteRole(Role $role)
    {
        if ($role->is_system || $role->name === 'admin') {
            abort(422, 'System roles cannot be deleted.');
        }

        if (User::where('role', $role->name)->exists()) {
            abort(422, 'This role is assigned to users. Reassign those users before deleting the role.');
        }

        DB::transaction(function () use ($role) {
            RolePermission::where('role', $role->name)->delete();
            $role->delete();
        });

        return response()->json([
            'message' => 'Role deleted successfully.',
        ]);
    }

    private function syncPermissions(string $role, array $permissionNames): void
    {
        $ids = Permission::whereIn('name', array_values(array_unique($permissionNames)))
            ->pluck('id', 'name');

        RolePermission::where('role', $role)->delete();

        foreach ($ids as $permissionId) {
            RolePermission::create([
                'role' => $role,
                'permission_id' => $permissionId,
            ]);
        }
    }

    private function permissionNames(string $role): array
    {
        return RolePermission::query()
            ->join('permissions', 'permissions.id', '=', 'role_permissions.permission_id')
            ->where('role_permissions.role', $role)
            ->orderBy('permissions.name')
            ->pluck('permissions.name')
            ->values()
            ->all();
    }
}
