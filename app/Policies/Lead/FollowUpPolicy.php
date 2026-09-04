<?php

namespace App\Policies\Lead;

use App\Models\Lead\FollowUp;
use App\Policies\OwnershipPolicy;
use Illuminate\Database\Eloquent\Model;

class FollowUpPolicy extends OwnershipPolicy
{
    protected function ownerId(Model $record): ?int
    {
        /** @var FollowUp $record */
        return $record->loadMissing('lead')->lead?->owner_id;
    }
}
