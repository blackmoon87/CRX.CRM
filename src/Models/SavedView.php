<?php

declare(strict_types=1);

namespace App\Models;

use Spartan\Model;

class SavedView extends Model
{
    protected string $table = 'saved_views';
    protected bool $timestamps = true;
}
