<?php

declare(strict_types=1);

namespace App\Models;

use Spartan\Model;

class Interaction extends Model
{
    protected string $table = 'interactions';
    protected bool $timestamps = false;
}
