<?php

declare(strict_types=1);

namespace App\Models;

use Spartan\Model;
use Spartan\RelationQuery;

class ActivityLog extends Model
{
    protected string $table = 'activity_log';
    protected bool $timestamps = false;

    public function user(): RelationQuery
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function workspace(): RelationQuery
    {
        return $this->belongsTo(Workspace::class, 'workspace_id');
    }
}
