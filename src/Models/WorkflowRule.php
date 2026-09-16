<?php

declare(strict_types=1);

namespace App\Models;

use Spartan\Model;

class WorkflowRule extends Model
{
    protected string $table = 'workflow_rules';
    protected bool $timestamps = true;
}
