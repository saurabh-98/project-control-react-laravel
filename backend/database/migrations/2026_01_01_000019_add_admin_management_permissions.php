<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('permissions') || !Schema::hasTable('role_permissions')) {
            return;
        }

        $permissions = [
            [
                'name' => 'user.manage',
                'label' => 'Manage users',
                'group' => 'administration',
            ],
            [
                'name' => 'role.manage',
                'label' => 'Manage roles and permissions',
                'group' => 'administration',
            ],
        ];

        foreach ($permissions as $permission) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $permission['name']],
                [
                    'label' => $permission['label'],
                    'group' => $permission['group'],
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        $ids = DB::table('permissions')
            ->whereIn('name', ['user.manage', 'role.manage'])
            ->pluck('id', 'name');

        foreach ($ids as $id) {
            DB::table('role_permissions')->updateOrInsert(
                [
                    'role' => 'admin',
                    'permission_id' => $id,
                ],
                []
            );
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

        $ids = DB::table('permissions')
            ->whereIn('name', ['user.manage', 'role.manage'])
            ->pluck('id');

        if ($ids->isNotEmpty() && Schema::hasTable('role_permissions')) {
            DB::table('role_permissions')
                ->where('role', 'admin')
                ->whereIn('permission_id', $ids)
                ->delete();
        }

        DB::table('permissions')
            ->whereIn('name', ['user.manage', 'role.manage'])
            ->delete();
    }
};
