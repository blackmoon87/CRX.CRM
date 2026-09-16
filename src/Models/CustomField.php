<?php

declare(strict_types=1);

namespace App\Models;

use Spartan\Model;
use Spartan\RelationQuery;

class CustomField extends Model
{
    protected string $table = 'custom_fields';
    protected bool $timestamps = true;

    public function values(): RelationQuery
    {
        return $this->hasMany(CustomFieldValue::class, 'custom_field_id');
    }
}
