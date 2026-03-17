<?php

namespace Xefi\LaravelOSDD\Tests\Console\Commands\Make;

use Orchestra\Testbench\Concerns\InteractsWithPublishedFiles;
use Xefi\LaravelOSDD\Tests\TestCase;

class ConsoleMakeCommandTest extends TestCase
{
    use InteractsWithPublishedFiles;

    protected function tearDown(): void
    {
        $this->app['files']->deleteDirectory($this->app->basePath('functional/test-layer/src/Console'));

        parent::tearDown();
    }

    public function testItGeneratesCommandFileInCorrectPath(): void
    {
        $this->artisan('osdd:command', ['name' => 'SendReportCommand', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/src/Console/Commands/SendReportCommand.php');
    }

    public function testItGeneratesCommandWithCorrectNamespace(): void
    {
        $this->artisan('osdd:command', ['name' => 'SendReportCommand', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace Functional\TestLayer\Console\Commands;',
            'class SendReportCommand extends Command',
        ], 'functional/test-layer/src/Console/Commands/SendReportCommand.php');
    }

    public function testItPromptsForLayerWhenNotProvided(): void
    {
        $this->artisan('osdd:command', ['name' => 'SendReportCommand'])
            ->expectsSearch('Which layer should this be generated in?', 'functional/test-layer', '', ['functional/test-layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/src/Console/Commands/SendReportCommand.php');
    }
}
