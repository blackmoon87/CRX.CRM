<?php

declare(strict_types=1);

namespace App\Models;

use Spartan\Model;

class PipelineStage extends Model
{
    protected string $table = 'pipeline_stages';
    protected bool $timestamps = false;
}
