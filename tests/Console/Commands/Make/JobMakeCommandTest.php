<?php

namespace Xefi\LaravelOSDD\Tests\Console\Commands\Make;

use Orchestra\Testbench\Concerns\InteractsWithPublishedFiles;
use Xefi\LaravelOSDD\Tests\TestCase;

class JobMakeCommandTest extends TestCase
{
    use InteractsWithPublishedFiles;

    protected function tearDown(): void
    {
        $this->app['files']->deleteDirectory($this->app->basePath('functional/test-layer/src/Jobs'));

        parent::tearDown();
    }

    public function testItGeneratesJobFileInCorrectPath(): void
    {
        $this->artisan('osdd:job', ['name' => 'ProcessPayment', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/src/Jobs/ProcessPayment.php');
    }

    public function testItGeneratesJobWithCorrectNamespace(): void
    {
        $this->artisan('osdd:job', ['name' => 'ProcessPayment', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace Functional\TestLayer\Jobs;',
            'class ProcessPayment',
        ], 'functional/test-layer/src/Jobs/ProcessPayment.php');
    }

    public function testItPromptsForLayerWhenNotProvided(): void
    {
        $this->artisan('osdd:job', ['name' => 'ProcessPayment'])
            ->expectsSearch('Which layer should this be generated in?', 'functional/test-layer', '', ['functional/test-layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/src/Jobs/ProcessPayment.php');
    }
}
