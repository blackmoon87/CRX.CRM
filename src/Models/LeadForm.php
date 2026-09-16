<?php

declare(strict_types=1);

namespace App\Models;

use Spartan\Model;

class LeadForm extends Model
{
    protected string $table = 'lead_forms';
    protected bool $timestamps = true;
}
