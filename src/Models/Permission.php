<?php

declare(strict_types=1);

namespace App\Models;

use Spartan\Model;
use Spartan\RelationQuery;

class Permission extends Model
{
    protected string $table = 'permissions';
    protected bool $timestamps = false;

    public function roles(): RelationQuery
    {
        return $this->belongsToMany(Role::class, 'role_permissions', 'permission_id', 'role_id');
    }
}
