<?php

namespace App\Providers;

use App\Models\SmartLink\Prospect;
use App\Policies\SmartLink\ProspectPolicy;
use App\Services\SmartLink\Instagram\ProfileSource;
use App\Services\SmartLink\Instagram\WebProfileSource;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Swapping the Instagram provider is a config change, not a code change:
        // register the new class in services.instagram.sources and point
        // INSTAGRAM_SOURCE at it.
        $this->app->bind(ProfileSource::class, function () {
            $sources = config('services.instagram.sources', []);

            return $this->app->make($sources[config('services.instagram.source')] ?? WebProfileSource::class);
        });
    }

    public function boot(): void
    {
        Paginator::useBootstrapFive();

        // Registered explicitly: App\Models\SmartLink\Prospect lives under a
        // nested namespace, which Laravel's Policy naming-convention
        // auto-discovery does not follow.
        Gate::policy(Prospect::class, ProspectPolicy::class);
    }
}
