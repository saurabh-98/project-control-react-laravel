<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\ProjectConfigurationController;
use App\Http\Controllers\Admin\MasterDataController;
use App\Http\Controllers\Admin\UserRoleController;
use App\Models\Permission;
use App\Models\Role;
use App\Models\RolePermission;
use Illuminate\Support\Facades\Route;
Route::get('/login/roles', [AuthController::class, 'loginRoles']);
Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::middleware('permission:master.view')->group(function () {
        Route::get('/projects', [
            MasterDataController::class,
            'projects'
        ]);
        // Flat lookup endpoints used by Admin Master Data.
        Route::get('/divisions', [
            MasterDataController::class,
            'allDivisions'
        ]);
        Route::get('/sub-divisions', [
            MasterDataController::class,
            'allSubDivisions'
        ]);
        Route::get('/towers', [
            MasterDataController::class,
            'allTowers'
        ]);
        Route::get('/levels', [
            MasterDataController::class,
            'allLevels'
        ]);
        Route::get('/projects/{project}/divisions', [
            MasterDataController::class,
            'divisions'
        ]);
        Route::get('/divisions/{division}/sub-divisions', [
            MasterDataController::class,
            'subDivisions'
        ]);
        Route::get('/sub-divisions/{subDivision}/towers', [
            MasterDataController::class,
            'towers'
        ]);
        Route::get('/towers/{tower}/levels', [
            MasterDataController::class,
            'levels'
        ]);
        Route::get('/activities', [
            MasterDataController::class,
            'activities'
        ]);
        Route::get('/sub-activities', [
            MasterDataController::class,
            'subActivities'
        ]);
        Route::get('/apartments', [
            MasterDataController::class,
            'apartments'
        ]);
        Route::get('/typologies', [
            MasterDataController::class,
            'typologies'
        ]);
        Route::get('/uoms', [
            MasterDataController::class,
            'uoms'
        ]);
        Route::get('/priorities', [
            MasterDataController::class,
            'priorities'
        ]);
        Route::get('/reasons', [
            MasterDataController::class,
            'reasons'
        ]);
    });
    Route::prefix('admin')
        ->middleware('permission:master.manage')
        ->group(function () {
            // Specific priority routes MUST come before {resource}.
            Route::get('/master-data/priorities', [
                MasterDataController::class,
                'priorities'
            ]);
            Route::post('/master-data/priorities', [
                MasterDataController::class,
                'storePriority'
            ]);
            Route::put('/master-data/priorities/{id}', [
                MasterDataController::class,
                'updatePriority'
            ]);
            Route::delete('/master-data/priorities/{id}', [
                MasterDataController::class,
                'destroyPriority'
            ]);
            Route::get('/master-data/{resource}', [
                MasterDataController::class,
                'index'
            ]);
            Route::post('/master-data/{resource}', [
                MasterDataController::class,
                'store'
            ]);
            Route::put('/master-data/{resource}/{id}', [
                MasterDataController::class,
                'update'
            ]);
            Route::delete('/master-data/{resource}/{id}', [
                MasterDataController::class,
                'destroy'
            ]);
        });
    Route::get('/configurations', [
        ProjectConfigurationController::class,
        'index'
    ])->middleware('permission:configuration.view');
    Route::put('/configurations/{configuration}', [
        ProjectConfigurationController::class,
        'update'
    ])->middleware('permission:configuration.edit');
    Route::post('/configurations/bulk-update', [
        ProjectConfigurationController::class,
        'bulkUpdate'
    ])->middleware('permission:configuration.edit');
    Route::post('/configurations/import', [
        ProjectConfigurationController::class,
        'import'
    ])->middleware('permission:configuration.import');
    Route::get('/configurations/export', [
        ProjectConfigurationController::class,
        'export'
    ])->middleware('permission:configuration.export');
    Route::get('/plans', [
        PlanController::class,
        'index'
    ])->middleware('permission:plan.view');
    Route::get('/plans/{plan}', [
        PlanController::class,
        'show'
    ])->middleware('permission:plan.view');
    Route::post('/plans/build', [
        PlanController::class,
        'build'
    ])->middleware('permission:plan.view');
    Route::post('/plans/save', [
        PlanController::class,
        'save'
    ])->middleware('permission:plan.create');
    Route::post('/plans/{plan}/submit', [
        PlanController::class,
        'submit'
    ])->middleware('permission:plan.submit');
    Route::prefix('admin')->group(function () {
        Route::get('/users', [
            UserRoleController::class,
            'users'
        ])->middleware('permission:user.manage');
        Route::post('/users', [
            UserRoleController::class,
            'storeUser'
        ])->middleware('permission:user.manage');
        Route::put('/users/{user}', [
            UserRoleController::class,
            'updateUser'
        ])->middleware('permission:user.manage');
        Route::delete('/users/{user}', [
            UserRoleController::class,
            'deleteUser'
        ])->middleware('permission:user.manage');
        Route::get('/roles', [
            UserRoleController::class,
            'roles'
        ])->middleware('permission:role.manage');
        Route::put('/roles/{role}/permissions', [
            UserRoleController::class,
            'updatePermissions'
        ])->middleware('permission:role.manage');
        Route::put('/roles/{role}', function (\Illuminate\Http\Request $request, int $role) {
            $roleModel = Role::findOrFail($role);
            $validated = $request->validate([
                'name' => ['sometimes', 'string', 'max:100'],
                'label' => ['sometimes', 'string', 'max:255'],
                'permissions' => ['nullable', 'array'],
                'permissions.\\\\\\\*' => ['string', 'exists:permissions,name'],
            ]);
            $oldName = $roleModel->name;
            if (array_key_exists('name', $validated)) {
                $roleModel->name = $validated['name'];
            }
            if (array_key_exists('label', $validated)) {
                $roleModel->label = $validated['label'];
            }
            $roleModel->save();
            if (array_key_exists('permissions', $validated)) {
                RolePermission::where('role', $oldName)->delete();
                $permissionIds = Permission::whereIn(
                    'name',
                    $validated['permissions'] ?? []
                )->pluck('id');
                foreach ($permissionIds as $permissionId) {
                    RolePermission::create([
                        'role' => $roleModel->name,
                        'permission_id' => $permissionId,
                    ]);
                }
            }
            return response()->json([
                'message' => 'Role updated successfully.',
                'data' => $roleModel->fresh(),
            ]);
        })->middleware('permission:role.manage');
        Route::delete('/roles/{role}', function (int $role) {
            $roleModel = Role::findOrFail($role);
            if ($roleModel->name === 'admin') {
                return response()->json([
                    'message' => 'Administrator role cannot be deleted.'
                ], 422);
            }
            RolePermission::where('role', $roleModel->name)->delete();
            $roleModel->delete();
            return response()->json([
                'message' => 'Role deleted successfully.'
            ]);
        })->middleware('permission:role.manage');
    });
});
