<?php

declare(strict_types=1);

namespace App\Models;

use Spartan\Model;
use Spartan\RelationQuery;

class CustomFieldValue extends Model
{
    protected string $table = 'custom_field_values';
    protected bool $timestamps = true;

    public function customField(): RelationQuery
    {
        return $this->belongsTo(CustomField::class, 'custom_field_id');
    }
}
