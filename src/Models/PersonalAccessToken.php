<?php

declare(strict_types=1);

namespace App\Models;

use Spartan\Model;
use Spartan\RelationQuery;

class PersonalAccessToken extends Model
{
    protected string $table = 'personal_access_tokens';
    protected bool $timestamps = true;

    public function user(): RelationQuery
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function workspace(): RelationQuery
    {
        return $this->belongsTo(Workspace::class, 'workspace_id');
    }
}
