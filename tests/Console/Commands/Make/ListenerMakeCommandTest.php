<?php

namespace Xefi\LaravelOSDD\Tests\Console\Commands\Make;

use Orchestra\Testbench\Concerns\InteractsWithPublishedFiles;
use Xefi\LaravelOSDD\Tests\TestCase;

class ListenerMakeCommandTest extends TestCase
{
    use InteractsWithPublishedFiles;

    protected function tearDown(): void
    {
        $this->app['files']->deleteDirectory($this->app->basePath('functional/test-layer/src/Listeners'));
        $this->app['files']->deleteDirectory($this->app->basePath('functional/test-layer/src/Events'));

        parent::tearDown();
    }

    public function testItGeneratesListenerFileInCorrectPath(): void
    {
        $this->artisan('osdd:listener', ['name' => 'SendWelcomeEmail', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/src/Listeners/SendWelcomeEmail.php');
    }

    public function testItGeneratesListenerWithCorrectNamespace(): void
    {
        $this->artisan('osdd:listener', ['name' => 'SendWelcomeEmail', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace Functional\TestLayer\Listeners;',
            'class SendWelcomeEmail',
        ], 'functional/test-layer/src/Listeners/SendWelcomeEmail.php');
    }

    public function testItPromptsForLayerWhenNotProvided(): void
    {
        $this->artisan('osdd:listener', ['name' => 'SendWelcomeEmail'])
            ->expectsSearch('Which layer should this be generated in?', 'functional/test-layer', '', ['functional/test-layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/src/Listeners/SendWelcomeEmail.php');
    }

    public function testItGeneratesListenerWithEventInLayerNamespace(): void
    {
        $this->artisan('osdd:listener', ['name' => 'SendWelcomeEmail', '--event' => 'UserRegistered', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'use Functional\TestLayer\Events\UserRegistered;',
        ], 'functional/test-layer/src/Listeners/SendWelcomeEmail.php');
    }

    public function testItGeneratesNestedListenerInCorrectPath(): void
    {
        $this->artisan('osdd:listener', ['name' => 'Auth/HandleLogin', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/src/Listeners/Auth/HandleLogin.php');
    }

    public function testItGeneratesNestedListenerWithCorrectNamespace(): void
    {
        $this->artisan('osdd:listener', ['name' => 'Auth/HandleLogin', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace Functional\TestLayer\Listeners\Auth;',
            'class HandleLogin',
        ], 'functional/test-layer/src/Listeners/Auth/HandleLogin.php');
    }
}
