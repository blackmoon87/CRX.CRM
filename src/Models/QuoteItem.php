<?php

declare(strict_types=1);

namespace App\Models;

use Spartan\Model;

class QuoteItem extends Model
{
    protected string $table = 'quote_items';
    protected bool $timestamps = false;
}
