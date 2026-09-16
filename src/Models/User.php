<?php

declare(strict_types=1);

namespace App\Models;

use Spartan\Model;
use Spartan\RelationQuery;
use Spartan\Traits\HasAuthorization;

class User extends Model
{
    use HasAuthorization;

    protected string $table = 'users';
    protected bool $timestamps = true;

    public function memberships(): RelationQuery
    {
        return $this->hasMany(Membership::class, 'user_id');
    }

    public function findByEmail(string $email): ?self
    {
        return $this->findInstanceBy('email', $email);
    }
}
