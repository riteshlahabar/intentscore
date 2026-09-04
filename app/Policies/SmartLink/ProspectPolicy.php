<?php

namespace App\Policies\SmartLink;

use App\Models\SmartLink\Prospect;
use App\Policies\OwnershipPolicy;
use Illuminate\Database\Eloquent\Model;

class ProspectPolicy extends OwnershipPolicy
{
    protected function ownerId(Model $record): ?int
    {
        /** @var Prospect $record */
        return $record->salesperson_id;
    }
}
