<?php

declare(strict_types=1);

namespace App\Models;

use Spartan\Model;
use Spartan\RelationQuery;

class Task extends Model
{
    protected string $table = 'tasks';
    protected bool $timestamps = true;

    public function workspace(): RelationQuery
    {
        return $this->belongsTo(Workspace::class, 'workspace_id');
    }

    public function assignedUser(): RelationQuery
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }
}
