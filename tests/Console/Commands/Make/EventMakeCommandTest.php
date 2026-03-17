<?php

namespace Xefi\LaravelOSDD\Tests\Console\Commands\Make;

use Orchestra\Testbench\Concerns\InteractsWithPublishedFiles;
use Xefi\LaravelOSDD\Tests\TestCase;

class EventMakeCommandTest extends TestCase
{
    use InteractsWithPublishedFiles;

    protected function tearDown(): void
    {
        $this->app['files']->deleteDirectory($this->app->basePath('functional/test-layer/src/Events'));

        parent::tearDown();
    }

    public function testItGeneratesEventFileInCorrectPath(): void
    {
        $this->artisan('osdd:event', ['name' => 'UserRegistered', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/src/Events/UserRegistered.php');
    }

    public function testItGeneratesEventWithCorrectNamespace(): void
    {
        $this->artisan('osdd:event', ['name' => 'UserRegistered', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace Functional\TestLayer\Events;',
            'class UserRegistered',
        ], 'functional/test-layer/src/Events/UserRegistered.php');
    }

    public function testItPromptsForLayerWhenNotProvided(): void
    {
        $this->artisan('osdd:event', ['name' => 'UserRegistered'])
            ->expectsSearch('Which layer should this be generated in?', 'functional/test-layer', '', ['functional/test-layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/src/Events/UserRegistered.php');
    }

    public function testItGeneratesNestedEventInCorrectPath(): void
    {
        $this->artisan('osdd:event', ['name' => 'Auth/UserLoggedIn', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/src/Events/Auth/UserLoggedIn.php');
    }

    public function testItGeneratesNestedEventWithCorrectNamespace(): void
    {
        $this->artisan('osdd:event', ['name' => 'Auth/UserLoggedIn', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace Functional\TestLayer\Events\Auth;',
            'class UserLoggedIn',
        ], 'functional/test-layer/src/Events/Auth/UserLoggedIn.php');
    }
}
