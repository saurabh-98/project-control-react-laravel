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

        DB::table('permissions')->updateOrInsert(
            ['name' => 'master.manage'],
            [
                'label' => 'Manage project master data',
                'group' => 'administration',
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        $permissionId = DB::table('permissions')
            ->where('name', 'master.manage')
            ->value('id');

        DB::table('role_permissions')->updateOrInsert(
            [
                'role' => 'admin',
                'permission_id' => $permissionId,
            ],
            []
        );
    }

    public function down(): void
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

        $id = DB::table('permissions')
            ->where('name', 'master.manage')
            ->value('id');

        if ($id && Schema::hasTable('role_permissions')) {
            DB::table('role_permissions')
                ->where('permission_id', $id)
                ->delete();
        }

        DB::table('permissions')
            ->where('id', $id)
            ->delete();
    }
};
