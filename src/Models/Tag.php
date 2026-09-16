<?php

declare(strict_types=1);

namespace App\Models;

use Spartan\Model;

class Tag extends Model
{
    protected string $table = 'tags';
    protected bool $timestamps = false;
}
