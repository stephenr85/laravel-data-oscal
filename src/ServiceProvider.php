<?php

namespace Rushing\DataOscal;

use Rushing\Popcorn\Registries\ScopesRegistrant;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

/**
 * Registers the OSCAL projection registry as a singleton — the mirror of the schema-org leaf's
 * provider. Hosts register their per-Data-class {@see Contracts\OscalProjector}s against it.
 */
class ServiceProvider extends PackageServiceProvider
{
    use ScopesRegistrant;

    /** Every registry write in register()/boot() that names no `$by` records this package. */
    protected function registrant(): string
    {
        return 'rushing/laravel-data-oscal';
    }

    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-data-oscal')
            ->hasConfigFile('data-oscal');
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(OscalProjectionRegistry::class);
    }
}
