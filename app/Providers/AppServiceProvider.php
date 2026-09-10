<?php

namespace App\Providers;

use App\Models\SmartLink\Prospect;
use App\Policies\SmartLink\ProspectPolicy;
use App\Services\SmartLink\Instagram\ProfileSource;
use App\Services\SmartLink\Instagram\WebProfileSource;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
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

        $this->registerAssetVersioning();
    }

    /**
     * @assetv('css/x.css') — asset() plus a ?v= stamp taken from the file's mtime.
     *
     * The Smart Page CSS is served straight from /public with no build step, so a
     * browser that cached it keeps the old copy after a deploy and the change looks
     * like it never shipped. The mtime changes whenever the file is uploaded, which
     * makes the URL change, which is enough to defeat the cache. Missing files fall
     * back to a bare asset() rather than erroring.
     */
    private function registerAssetVersioning(): void
    {
        Blade::directive('assetv', function (string $expression) {
            return "<?php echo e(\App\Providers\AppServiceProvider::versionedAsset({$expression})); ?>";
        });
    }

    public static function versionedAsset(string $path): string
    {
        $file = public_path($path);

        return asset($path).(is_file($file) ? '?v='.filemtime($file) : '');
    }
}
