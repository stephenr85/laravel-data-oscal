<?php

namespace Rushing\DataOscal\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Rushing\DataOscal\ServiceProvider;
use Spatie\LaravelData\LaravelDataServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            LaravelDataServiceProvider::class,
            ServiceProvider::class,
        ];
    }
}
