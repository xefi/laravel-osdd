<?php

namespace Xefi\LaravelOSDD\Tests\Console\Commands\Make;

use Orchestra\Testbench\Concerns\InteractsWithPublishedFiles;
use Xefi\LaravelOSDD\Tests\TestCase;

class NotificationMakeCommandTest extends TestCase
{
    use InteractsWithPublishedFiles;

    protected function tearDown(): void
    {
        $this->app['files']->deleteDirectory($this->app->basePath('functional/test-layer/src/Notifications'));

        parent::tearDown();
    }

    public function testItGeneratesNotificationFileInCorrectPath(): void
    {
        $this->artisan('osdd:notification', ['name' => 'WelcomeNotification', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/src/Notifications/WelcomeNotification.php');
    }

    public function testItGeneratesNotificationWithCorrectNamespace(): void
    {
        $this->artisan('osdd:notification', ['name' => 'WelcomeNotification', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace Functional\TestLayer\Notifications;',
            'class WelcomeNotification extends Notification',
        ], 'functional/test-layer/src/Notifications/WelcomeNotification.php');
    }

    public function testItPromptsForLayerWhenNotProvided(): void
    {
        $this->artisan('osdd:notification', ['name' => 'WelcomeNotification'])
            ->expectsSearch('Which layer should this be generated in?', 'functional/test-layer', '', ['functional/test-layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/src/Notifications/WelcomeNotification.php');
    }

    public function testItGeneratesNestedNotificationInCorrectPath(): void
    {
        $this->artisan('osdd:notification', ['name' => 'Auth/PasswordResetNotification', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/src/Notifications/Auth/PasswordResetNotification.php');
    }

    public function testItGeneratesNestedNotificationWithCorrectNamespace(): void
    {
        $this->artisan('osdd:notification', ['name' => 'Auth/PasswordResetNotification', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace Functional\TestLayer\Notifications\Auth;',
            'class PasswordResetNotification extends Notification',
        ], 'functional/test-layer/src/Notifications/Auth/PasswordResetNotification.php');
    }
}
