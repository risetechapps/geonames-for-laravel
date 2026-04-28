<?php

namespace RiseTechApps\Geonames;

use Illuminate\Support\ServiceProvider;
use RiseTechApps\Geonames\Console\Commands\GeonamesBenchmark;
use RiseTechApps\Geonames\Console\Commands\GeonamesCacheClear;
use RiseTechApps\Geonames\Console\Commands\GeonamesCacheWarm;
use RiseTechApps\Geonames\Console\Commands\GeonamesInstall;
use RiseTechApps\Geonames\Console\Commands\GeonamesInstallData;
use RiseTechApps\Geonames\Features\Country;
use RiseTechApps\Geonames\Features\State;

class GeonamesServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/config.php' => config_path('geonames.php'),
            ], 'geonames-config');

            $this->commands([
                GeonamesCacheWarm::class,
                GeonamesCacheClear::class,
                GeonamesInstall::class,
                GeonamesInstallData::class,
                GeonamesBenchmark::class,
            ]);
        }
    }

    /**
     * Register the application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'geonames');

        $this->app->singleton('geonames', function () {
            return new Geonames();
        });

        $this->app->singleton(Geonames::class, function () {
            return new Geonames();
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<string>
     */
    public function provides(): array
    {
        return ['geonames', Geonames::class];
    }
}
