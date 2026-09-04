<?php

namespace App\Providers;

use App\Models\Client\Client;
use App\Models\Lead\FollowUp;
use App\Models\Lead\Lead;
use App\Models\Presentation\Presentation;
use App\Models\SmartLink\Prospect;
use App\Policies\Client\ClientPolicy;
use App\Policies\Lead\FollowUpPolicy;
use App\Policies\Lead\LeadPolicy;
use App\Policies\Presentation\PresentationPolicy;
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

        // Registered explicitly: these models live under nested namespaces
        // (App\Models\SmartLink\Prospect, App\Models\Lead\Lead, ...), which
        // Laravel's Policy naming-convention auto-discovery does not follow.
        Gate::policy(Prospect::class, ProspectPolicy::class);
        Gate::policy(Presentation::class, PresentationPolicy::class);
        Gate::policy(Client::class, ClientPolicy::class);
        Gate::policy(Lead::class, LeadPolicy::class);
        Gate::policy(FollowUp::class, FollowUpPolicy::class);
    }
}
