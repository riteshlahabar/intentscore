<?php

namespace App\Policies;

use App\Models\User;
use App\Services\Common\AccessService;
use Illuminate\Database\Eloquent\Model;

/**
 * Shared "salesperson may only act on their own record; admin sees
 * everything" rule used by every owned module (Prospects, Presentations, Clients,
 * Leads, Follow-ups). Centralising it here means a new controller action is
 * authorized correctly by construction - via Gate::authorize()/authorizeResource()
 * or the `can:` route middleware - instead of depending on every action remembering
 * to call a helper method by hand.
 */
abstract class OwnershipPolicy
{
    abstract protected function ownerId(Model $record): ?int;

    public function view(User $user, Model $record): bool
    {
        return $this->owns($user, $record);
    }

    public function update(User $user, Model $record): bool
    {
        return $this->owns($user, $record);
    }

    public function delete(User $user, Model $record): bool
    {
        return $this->owns($user, $record);
    }

    protected function owns(User $user, Model $record): bool
    {
        return AccessService::isPrivileged($user) || $this->ownerId($record) === $user->id;
    }
}
