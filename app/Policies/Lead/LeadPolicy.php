<?php

namespace App\Policies\Lead;

use App\Models\Lead\Lead;
use App\Policies\OwnershipPolicy;
use Illuminate\Database\Eloquent\Model;

class LeadPolicy extends OwnershipPolicy
{
    protected function ownerId(Model $record): ?int
    {
        /** @var Lead $record */
        return $record->owner_id;
    }
}
