<?php

declare(strict_types=1);

namespace App\Models;

use Spartan\Model;

class BookingSetting extends Model
{
    protected string $table = 'booking_settings';
    protected bool $timestamps = true;
}
