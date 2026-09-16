<?php

declare(strict_types=1);

namespace App\Models;

use Spartan\Model;

class EmailTracking extends Model
{
    protected string $table = 'email_trackings';
    protected bool $timestamps = true;
}
