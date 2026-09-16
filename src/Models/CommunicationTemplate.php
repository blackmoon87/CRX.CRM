<?php

declare(strict_types=1);

namespace App\Models;

use Spartan\Model;

class CommunicationTemplate extends Model
{
    protected string $table = 'communication_templates';
    protected bool $timestamps = true;
}
