<?php

namespace Xefi\LaravelOSDD\Tests\Console\Commands\Make;

use Orchestra\Testbench\Concerns\InteractsWithPublishedFiles;
use Xefi\LaravelOSDD\Tests\TestCase;

class ScopeMakeCommandTest extends TestCase
{
    use InteractsWithPublishedFiles;

    protected function tearDown(): void
    {
        $this->app['files']->deleteDirectory($this->app->basePath('functional/test-layer/src/Models/Scopes'));

        parent::tearDown();
    }

    public function testItGeneratesScopeFileInCorrectPath(): void
    {
        $this->artisan('osdd:scope', ['name' => 'ActiveScope', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/src/Models/Scopes/ActiveScope.php');
    }

    public function testItGeneratesScopeWithCorrectNamespace(): void
    {
        $this->artisan('osdd:scope', ['name' => 'ActiveScope', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace Functional\TestLayer\Models\Scopes;',
            'class ActiveScope implements Scope',
        ], 'functional/test-layer/src/Models/Scopes/ActiveScope.php');
    }

    public function testItPromptsForLayerWhenNotProvided(): void
    {
        $this->artisan('osdd:scope', ['name' => 'ActiveScope'])
            ->expectsSearch('Which layer should this be generated in?', 'functional/test-layer', '', ['functional/test-layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/src/Models/Scopes/ActiveScope.php');
    }
}
