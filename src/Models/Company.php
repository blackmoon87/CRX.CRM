<?php

declare(strict_types=1);

namespace App\Models;

use Spartan\Model;
use Spartan\RelationQuery;

class Company extends Model
{
    protected string $table = 'companies';
    protected bool $timestamps = true;

    public function workspace(): RelationQuery
    {
        return $this->belongsTo(Workspace::class, 'workspace_id');
    }

    public function people(): RelationQuery
    {
        return $this->hasMany(Person::class, 'company_id');
    }

    public function opportunities(): RelationQuery
    {
        return $this->hasMany(Opportunity::class, 'company_id');
    }

    public function notes(): RelationQuery
    {
        return $this->hasMany(Note::class, 'entity_id');
    }
}
