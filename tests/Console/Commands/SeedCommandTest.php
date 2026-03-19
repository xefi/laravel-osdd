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
        $this->app->make(SeederRegistry::class)->push(0, 'NonExistent\\Seeder');

        $this->artisan('osdd:seed')->assertExitCode(0);
    }

    public function testSeedersAreReturnedInPriorityOrder(): void
    {
        $registry = $this->app->make(SeederRegistry::class);
        $registry->push(10, 'Seeder\\C');
        $registry->push(-5, 'Seeder\\A');
        $registry->push(0,  'Seeder\\B');

        $this->assertSame(['Seeder\\A', 'Seeder\\B', 'Seeder\\C'], $registry->seeders());
    }

    public function testSeedersWithSamePriorityPreserveRegistrationOrder(): void
    {
        $registry = $this->app->make(SeederRegistry::class);
        $registry->push(0, 'Seeder\\First');
        $registry->push(0, 'Seeder\\Second');

        $this->assertSame(['Seeder\\First', 'Seeder\\Second'], $registry->seeders());
    }

    public function testItRunsMigrateFreshWhenFreshOptionIsGiven(): void
    {
        $this->artisan('osdd:seed --fresh')->assertExitCode(0);
    }

    public function testItRunsMigrateRefreshWhenRefreshOptionIsGiven(): void
    {
        $this->artisan('osdd:seed --refresh')->assertExitCode(0);
    }

    public function testFreshTakesPrecedenceOverRefresh(): void
    {
        $this->artisan('osdd:seed --fresh --refresh')->assertExitCode(0);
    }
}
