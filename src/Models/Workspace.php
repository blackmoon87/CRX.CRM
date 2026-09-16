<?php

declare(strict_types=1);

namespace App\Models;

use Spartan\Model;
use Spartan\RelationQuery;

class Workspace extends Model
{
    protected string $table = 'workspaces';
    protected bool $timestamps = true;

    public function owner(): RelationQuery
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function memberships(): RelationQuery
    {
        return $this->hasMany(Membership::class, 'workspace_id');
    }

    public function companies(): RelationQuery
    {
        return $this->hasMany(Company::class, 'workspace_id');
    }

    public function people(): RelationQuery
    {
        return $this->hasMany(Person::class, 'workspace_id');
    }

    public function opportunities(): RelationQuery
    {
        return $this->hasMany(Opportunity::class, 'workspace_id');
    }

    public function tasks(): RelationQuery
    {
        return $this->hasMany(Task::class, 'workspace_id');
    }

    public function notes(): RelationQuery
    {
        return $this->hasMany(Note::class, 'workspace_id');
    }

    public function customFields(): RelationQuery
    {
        return $this->hasMany(CustomField::class, 'workspace_id');
    }

    public function tokens(): RelationQuery
    {
        return $this->hasMany(PersonalAccessToken::class, 'workspace_id');
    }
}
