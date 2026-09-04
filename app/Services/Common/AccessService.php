<?php

namespace App\Services\Common;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Query-level ownership scoping and input defaulting for the "salesperson sees only
 * their own records" rule. Authorization of a specific already-loaded record is a
 * separate concern and lives in the Policy classes under app/Policies, which share
 * the role check below via isPrivileged() instead of duplicating it.
 */
class AccessService
{
    private const PRIVILEGED_ROLES = ['super_admin', 'admin', 'sales_manager'];

    public static function isPrivileged(User $user): bool
    {
        return in_array($user->role, self::PRIVILEGED_ROLES, true);
    }

    public function isSalesperson(): bool
    {
        return auth()->check() && ! self::isPrivileged(auth()->user());
    }

    public function scopeOwned(Builder $query, string $column = 'owner_id'): Builder
    {
        return $this->isSalesperson() ? $query->where($column, auth()->id()) : $query;
    }

    public function enforceOwner(?int $requestedOwnerId): ?int
    {
        if ($this->isSalesperson()) {
            return auth()->id();
        }

        return $requestedOwnerId;
    }
}
