<?php

namespace Xefi\LaravelOSDD\Tests\Console\Commands\Make;

use Orchestra\Testbench\Concerns\InteractsWithPublishedFiles;
use Xefi\LaravelOSDD\Tests\TestCase;

class EnumMakeCommandTest extends TestCase
{
    use InteractsWithPublishedFiles;

    protected function tearDown(): void
    {
        $this->app['files']->deleteDirectory($this->app->basePath('functional/test-layer/src/Enums'));

        parent::tearDown();
    }

    public function testItGeneratesEnumFileInCorrectPath(): void
    {
        $this->artisan('osdd:enum', ['name' => 'Status', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/src/Enums/Status.php');
    }

    public function testItGeneratesEnumWithCorrectNamespace(): void
    {
        $this->artisan('osdd:enum', ['name' => 'Status', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace Functional\TestLayer\Enums;',
            'enum Status',
        ], 'functional/test-layer/src/Enums/Status.php');
    }

    public function testItPromptsForLayerWhenNotProvided(): void
    {
        $this->artisan('osdd:enum', ['name' => 'Status'])
            ->expectsSearch('Which layer should this be generated in?', 'functional/test-layer', '', ['functional/test-layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/src/Enums/Status.php');
    }
}
