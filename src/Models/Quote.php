<?php

declare(strict_types=1);

namespace App\Models;

use Spartan\Model;

class Quote extends Model
{
    protected string $table = 'quotes';
    protected bool $timestamps = true;
}
