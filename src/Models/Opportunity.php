<?php

declare(strict_types=1);

namespace App\Models;

use Spartan\Model;
use Spartan\RelationQuery;

class Opportunity extends Model
{
    public const STAGE_LEAD = 'lead';
    public const STAGE_QUALIFIED = 'qualified';
    public const STAGE_PROPOSAL = 'proposal';
    public const STAGE_NEGOTIATION = 'negotiation';
    public const STAGE_CLOSED_WON = 'closed_won';
    public const STAGE_CLOSED_LOST = 'closed_lost';

    public const STAGES = [
        self::STAGE_LEAD        => 'Lead In',
        self::STAGE_QUALIFIED   => 'Qualified',
        self::STAGE_PROPOSAL    => 'Proposal Sent',
        self::STAGE_NEGOTIATION => 'Negotiation',
        self::STAGE_CLOSED_WON  => 'Won',
        self::STAGE_CLOSED_LOST => 'Lost',
    ];

    protected string $table = 'opportunities';
    protected bool $timestamps = true;

    public function workspace(): RelationQuery
    {
        return $this->belongsTo(Workspace::class, 'workspace_id');
    }

    public function company(): RelationQuery
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function person(): RelationQuery
    {
        return $this->belongsTo(Person::class, 'person_id');
    }
}
