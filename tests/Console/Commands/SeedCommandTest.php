<?php

namespace Xefi\LaravelOSDD\Tests\Console\Commands;

use Xefi\LaravelOSDD\SeederRegistry;
use Xefi\LaravelOSDD\Tests\TestCase;

class SeedCommandTest extends TestCase
{
    public function testItRunsSuccessfullyWhenNoSeedersAreRegistered(): void
    {
        $this->artisan('osdd:seed')->assertExitCode(0);
    }

    public function testItWarnsWhenNoSeedersAreRegistered(): void
    {
        $this->artisan('osdd:seed')
            ->expectsOutputToContain('No OSDD seeders registered')
            ->assertExitCode(0);
    }

    public function testItSkipsPathsThatDoNotExist(): void
    {
        $this->app->make(SeederRegistry::class)->loadFrom('/non/existent/path');

        $this->artisan('osdd:seed')->assertExitCode(0);
    }
}
