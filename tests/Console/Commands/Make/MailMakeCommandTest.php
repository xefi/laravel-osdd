<?php

namespace Xefi\LaravelOSDD\Tests\Console\Commands\Make;

use Orchestra\Testbench\Concerns\InteractsWithPublishedFiles;
use Xefi\LaravelOSDD\Tests\TestCase;

class MailMakeCommandTest extends TestCase
{
    use InteractsWithPublishedFiles;

    protected function tearDown(): void
    {
        $this->app['files']->deleteDirectory($this->app->basePath('functional/test-layer/src/Mail'));

        parent::tearDown();
    }

    public function testItGeneratesMailFileInCorrectPath(): void
    {
        $this->artisan('osdd:mail', ['name' => 'WelcomeMail', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/src/Mail/WelcomeMail.php');
    }

    public function testItGeneratesMailWithCorrectNamespace(): void
    {
        $this->artisan('osdd:mail', ['name' => 'WelcomeMail', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace Functional\TestLayer\Mail;',
            'class WelcomeMail extends Mailable',
        ], 'functional/test-layer/src/Mail/WelcomeMail.php');
    }

    public function testItPromptsForLayerWhenNotProvided(): void
    {
        $this->artisan('osdd:mail', ['name' => 'WelcomeMail'])
            ->expectsSearch('Which layer should this be generated in?', 'functional/test-layer', '', ['functional/test-layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/src/Mail/WelcomeMail.php');
    }
}
