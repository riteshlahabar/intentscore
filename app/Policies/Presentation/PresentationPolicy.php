<?php

namespace App\Policies\Presentation;

use App\Models\Presentation\Presentation;
use App\Policies\OwnershipPolicy;
use Illuminate\Database\Eloquent\Model;

class PresentationPolicy extends OwnershipPolicy
{
    protected function ownerId(Model $record): ?int
    {
        /** @var Presentation $record */
        return $record->owner_id;
    }
}
