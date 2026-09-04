<?php

namespace App\Providers;

use App\Models\SmartLink\Prospect;
use App\Policies\SmartLink\ProspectPolicy;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Paginator::useBootstrapFive();

        // Registered explicitly: App\Models\SmartLink\Prospect lives under a
        // nested namespace, which Laravel's Policy naming-convention
        // auto-discovery does not follow.
        Gate::policy(Prospect::class, ProspectPolicy::class);
    }
}
