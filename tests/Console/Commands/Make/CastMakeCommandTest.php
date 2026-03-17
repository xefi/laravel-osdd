<?php

namespace Xefi\LaravelOSDD\Tests\Console\Commands\Make;

use Orchestra\Testbench\Concerns\InteractsWithPublishedFiles;
use Xefi\LaravelOSDD\Tests\TestCase;

class CastMakeCommandTest extends TestCase
{
    use InteractsWithPublishedFiles;

    protected function tearDown(): void
    {
        $this->app['files']->deleteDirectory($this->app->basePath('functional/test-layer/src/Casts'));

        parent::tearDown();
    }

    public function testItGeneratesCastFileInCorrectPath(): void
    {
        $this->artisan('osdd:cast', ['name' => 'MoneyAmount', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/src/Casts/MoneyAmount.php');
    }

    public function testItGeneratesCastWithCorrectNamespace(): void
    {
        $this->artisan('osdd:cast', ['name' => 'MoneyAmount', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace Functional\TestLayer\Casts;',
            'class MoneyAmount',
        ], 'functional/test-layer/src/Casts/MoneyAmount.php');
    }

    public function testItPromptsForLayerWhenNotProvided(): void
    {
        $this->artisan('osdd:cast', ['name' => 'MoneyAmount'])
            ->expectsSearch('Which layer should this be generated in?', 'functional/test-layer', '', ['functional/test-layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/src/Casts/MoneyAmount.php');
    }
}
