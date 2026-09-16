<?php

declare(strict_types=1);

namespace App\Models;

use Spartan\Model;

class Pipeline extends Model
{
    protected string $table = 'pipelines';
    protected bool $timestamps = true;
}
