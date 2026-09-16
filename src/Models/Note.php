<?php

declare(strict_types=1);

namespace App\Models;

use Spartan\Model;
use Spartan\RelationQuery;

class Note extends Model
{
    protected string $table = 'notes';
    protected bool $timestamps = true;

    public function workspace(): RelationQuery
    {
        return $this->belongsTo(Workspace::class, 'workspace_id');
    }

    public function user(): RelationQuery
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
