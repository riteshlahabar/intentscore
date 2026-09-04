<?php

namespace App\Policies\Client;

use App\Models\Client\Client;
use App\Policies\OwnershipPolicy;
use Illuminate\Database\Eloquent\Model;

class ClientPolicy extends OwnershipPolicy
{
    protected function ownerId(Model $record): ?int
    {
        /** @var Client $record */
        return $record->owner_id;
    }
}
