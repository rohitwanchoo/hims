<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    protected $primaryKey = 'permission_id';

    protected $fillable = [
        'permission_code',
        'permission_name',
        'module',
        'action',
        'description',
    ];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            Role::class,
            'role_permissions',
            'permission_id',
            'role_id'
        );
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'user_permissions',
            'permission_id',
            'user_id'
        )->withPivot('granted', 'assigned_by', 'assigned_at');
    }

    public function scopeByModule($query, string $module)
    {
        return $query->where('module', $module);
    }

    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    public static function findByCode(string $code): ?self
    {
        return static::where('permission_code', $code)->first();
    }
}
