<?php

namespace Xefi\LaravelOSDD\Tests\Console\Commands\Make;

use Orchestra\Testbench\Concerns\InteractsWithPublishedFiles;
use Xefi\LaravelOSDD\Tests\TestCase;

class MiddlewareMakeCommandTest extends TestCase
{
    use InteractsWithPublishedFiles;

    protected function tearDown(): void
    {
        $this->app['files']->deleteDirectory($this->app->basePath('functional/test-layer/src/Http/Middleware'));

        parent::tearDown();
    }

    public function testItGeneratesMiddlewareFileInCorrectPath(): void
    {
        $this->artisan('osdd:middleware', ['name' => 'Authenticate', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/src/Http/Middleware/Authenticate.php');
    }

    public function testItGeneratesMiddlewareWithCorrectNamespace(): void
    {
        $this->artisan('osdd:middleware', ['name' => 'Authenticate', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace Functional\TestLayer\Http\Middleware;',
            'class Authenticate',
        ], 'functional/test-layer/src/Http/Middleware/Authenticate.php');
    }

    public function testItPromptsForLayerWhenNotProvided(): void
    {
        $this->artisan('osdd:middleware', ['name' => 'Authenticate'])
            ->expectsSearch('Which layer should this be generated in?', 'functional/test-layer', '', ['functional/test-layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/src/Http/Middleware/Authenticate.php');
    }
}
