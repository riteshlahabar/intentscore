<?php

namespace App\Services\Common;

use Illuminate\Database\Eloquent\Builder;

class AccessService
{
    public function isSalesperson(): bool
    {
        return auth()->check() && auth()->user()->role === 'salesperson';
    }

    public function scopeOwned(Builder $query, string $column = 'owner_id'): Builder
    {
        return $this->isSalesperson() ? $query->where($column, auth()->id()) : $query;
    }

    public function assertOwner(?int $ownerId): void
    {
        if ($this->isSalesperson()) {
            abort_unless($ownerId === auth()->id(), 403);
        }
    }

    public function enforceOwner(?int $requestedOwnerId): ?int
    {
        if ($this->isSalesperson()) {
            return auth()->id();
        }

        return $requestedOwnerId;
    }
}
