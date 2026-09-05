<?php

namespace YourVendor\AIRotationManager\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use YourVendor\AIRotationManager\AIRotationServiceProvider;

abstract class TestCase extends Orchestra
{
    use \Illuminate\Foundation\Testing\Concerns\InteractsWithAuthentication;
    use \Illuminate\Foundation\Testing\Concerns\InteractsWithConsole;
    use \Illuminate\Foundation\Testing\Concerns\InteractsWithDatabase;
    use \Illuminate\Foundation\Testing\Concerns\MakesHttpRequests;

    protected function getPackageProviders($app): array
    {
        return [AIRotationServiceProvider::class];
    }
}
