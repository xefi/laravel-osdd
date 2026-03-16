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

    public function testItSkipsNonExistentSeederClasses(): void
    {
        $this->app->make(SeederRegistry::class)->push('NonExistent\\Seeder');

        $this->artisan('osdd:seed')->assertExitCode(0);
    }
}
