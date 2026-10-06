<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'password' => 'hashed',
    ];

    public const ROLES = [
        'admin',
        'project_manager',
        'engineer',
        'qs',
        'viewer',
    ];

    public function plans()
    {
        return $this->hasMany(Plan::class, 'created_by');
    }

    public function hasPermission(string $permission): bool
    {
        // Administrator has all permissions.
        if ($this->role === 'admin') {
            return true;
        }

        return DB::table('role_permissions')
            ->join(
                'permissions',
                'permissions.id',
                '=',
                'role_permissions.permission_id'
            )
            ->where('role_permissions.role', $this->role)
            ->where('permissions.name', $permission)
            ->exists();
    }

    public function permissions(): array
    {
        // Administrator gets every permission.
        if ($this->role === 'admin') {
            return DB::table('permissions')
                ->orderBy('name')
                ->pluck('name')
                ->values()
                ->all();
        }

        return DB::table('role_permissions')
            ->join(
                'permissions',
                'permissions.id',
                '=',
                'role_permissions.permission_id'
            )
            ->where('role_permissions.role', $this->role)
            ->orderBy('permissions.name')
            ->pluck('permissions.name')
            ->values()
            ->all();
    }

    /**
     * Check whether the user has at least one of the given abilities.
     *
     * This signature must remain compatible with Laravel's
     * Authenticatable::canAny() method.
     */
    public function canAny($abilities, $arguments = []): bool
    {
        $abilities = is_array($abilities)
            ? $abilities
            : [$abilities];

        foreach ($abilities as $ability) {
            if ($this->hasPermission($ability)) {
                return true;
            }
        }

        return false;
    }
}