<?php

namespace Xefi\LaravelOSDD\Tests\Console\Commands\Make;

use Orchestra\Testbench\Concerns\InteractsWithPublishedFiles;
use Xefi\LaravelOSDD\Tests\TestCase;

class ChannelMakeCommandTest extends TestCase
{
    use InteractsWithPublishedFiles;

    protected function tearDown(): void
    {
        $this->app['files']->deleteDirectory($this->app->basePath('functional/test-layer/src/Broadcasting'));

        parent::tearDown();
    }

    public function testItGeneratesChannelFileInCorrectPath(): void
    {
        $this->artisan('osdd:channel', ['name' => 'OrderChannel', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/src/Broadcasting/OrderChannel.php');
    }

    public function testItGeneratesChannelWithCorrectNamespace(): void
    {
        $this->artisan('osdd:channel', ['name' => 'OrderChannel', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace Functional\TestLayer\Broadcasting;',
            'class OrderChannel',
        ], 'functional/test-layer/src/Broadcasting/OrderChannel.php');
    }

    public function testItPromptsForLayerWhenNotProvided(): void
    {
        $this->artisan('osdd:channel', ['name' => 'OrderChannel'])
            ->expectsSearch('Which layer should this be generated in?', 'functional/test-layer', '', ['functional/test-layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/src/Broadcasting/OrderChannel.php');
    }
}
