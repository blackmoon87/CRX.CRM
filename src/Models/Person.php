<?php

declare(strict_types=1);

namespace App\Models;

use Spartan\Model;
use Spartan\RelationQuery;

class Person extends Model
{
    protected string $table = 'people';
    protected bool $timestamps = true;

    public function workspace(): RelationQuery
    {
        return $this->belongsTo(Workspace::class, 'workspace_id');
    }

    public function company(): RelationQuery
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function opportunities(): RelationQuery
    {
        return $this->hasMany(Opportunity::class, 'person_id');
    }
}
