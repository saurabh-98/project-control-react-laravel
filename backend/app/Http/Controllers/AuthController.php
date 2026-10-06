<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Login user.
     */
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::query()
            ->where('email', $data['email'])
            ->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials',
            ], 422);
        }

        // Remove existing tokens so only the latest login remains active.
        $user->tokens()->delete();

        // Create new Sanctum token.
        $token = $user->createToken('web')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $this->userPayload($user),
        ]);
    }

    /**
     * Get dynamic roles for the login screen.
     *
     * GET /api/login/roles
     *
     * `code`  = database role name
     * `label` = display name
     */
    public function loginRoles(): JsonResponse
    {
        $roles = Role::query()
            ->orderBy('id')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Default presentation information
        |--------------------------------------------------------------------------
        |
        | The role code always comes from the database.
        | These values are only fallback UI information.
        |
        */

        $profiles = [
            'admin' => [
                'label' => 'Administrator',
                'short' => 'Admin',
                'description' => 'Full system access',
                'access' => 'All modules',
                'icon' => 'A',
            ],

            'project_manager' => [
                'label' => 'Project Manager',
                'short' => 'Manager',
                'description' => 'Configuration & planning',
                'access' => 'Plans + Configuration',
                'icon' => 'PM',
            ],

            'engineer' => [
                'label' => 'Site Engineer',
                'short' => 'Engineer',
                'description' => 'Site planning & execution',
                'access' => 'Plans + Configuration',
                'icon' => 'SE',
            ],

            'qs' => [
                'label' => 'QS Team',
                'short' => 'QS',
                'description' => 'Quantity & configuration',
                'access' => 'Configuration + Plans',
                'icon' => 'QS',
            ],

            'viewer' => [
                'label' => 'Read Only User',
                'short' => 'Viewer',
                'description' => 'View project information',
                'access' => 'Read only',
                'icon' => 'RO',
            ],
        ];

        $data = $roles->map(function (Role $role) use ($profiles) {
            /*
            |--------------------------------------------------------------------------
            | Role code
            |--------------------------------------------------------------------------
            |
            | Example:
            | admin
            | project_manager
            | engineer
            |
            */

            $code = (string) $role->name;

            /*
            |--------------------------------------------------------------------------
            | Role display label
            |--------------------------------------------------------------------------
            |
            | If roles.label exists, use it.
            | Otherwise use the default profile label.
            |
            */

            $profile = $profiles[$code] ?? null;

            $label = $role->label
                ?: ($profile['label'] ?? ucwords(
                    str_replace('_', ' ', $code)
                ));

            /*
            |--------------------------------------------------------------------------
            | Find first user belonging to this role
            |--------------------------------------------------------------------------
            */

            $user = User::query()
                ->where('role', $code)
                ->orderBy('id')
                ->first();

            /*
            |--------------------------------------------------------------------------
            | Fallback UI values for custom roles
            |--------------------------------------------------------------------------
            */

            $short = $profile['short']
                ?? strtoupper(
                    substr(
                        preg_replace('/[^A-Za-z]/', '', $code),
                        0,
                        2
                    )
                );

            $description = $profile['description']
                ?? 'System user';

            $access = $profile['access']
                ?? 'Assigned permissions';

            $icon = $profile['icon']
                ?? strtoupper(
                    substr(
                        preg_replace('/[^A-Za-z]/', '', $code),
                        0,
                        2
                    )
                );

            return [
                /*
                |--------------------------------------------------------------------------
                | Primary organization/role fields
                |--------------------------------------------------------------------------
                */

                'id' => $role->id,

                'code' => $code,

                'label' => $label,

                /*
                |--------------------------------------------------------------------------
                | Backward-compatible frontend fields
                |--------------------------------------------------------------------------
                */

                'role' => $code,

                'title' => $label,

                'short' => $short,

                'description' => $description,

                'access' => $access,

                'icon' => $icon,

                /*
                |--------------------------------------------------------------------------
                | Login user
                |--------------------------------------------------------------------------
                */

                'email' => $user?->email,

                'user_id' => $user?->id,

                'has_user' => $user !== null,
            ];
        })->values();

        return response()->json([
            'data' => $data,
        ]);
    }

    /**
     * Get current authenticated user.
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $this->userPayload(
                $request->user()
            ),
        ]);
    }

    /**
     * Logout current user.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()
            ->currentAccessToken()
            ?->delete();

        return response()->json([
            'message' => 'Logged out',
        ]);
    }

    /**
     * Build authenticated user payload.
     */
    private function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'permissions' => $user->permissions(),
        ];
    }
}