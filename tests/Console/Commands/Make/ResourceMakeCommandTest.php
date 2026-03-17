<?php

namespace Xefi\LaravelOSDD\Tests\Console\Commands\Make;

use Orchestra\Testbench\Concerns\InteractsWithPublishedFiles;
use Xefi\LaravelOSDD\Tests\TestCase;

class ResourceMakeCommandTest extends TestCase
{
    use InteractsWithPublishedFiles;

    protected function tearDown(): void
    {
        $this->app['files']->deleteDirectory($this->app->basePath('functional/test-layer/src/Http/Resources'));

        parent::tearDown();
    }

    public function testItGeneratesResourceFileInCorrectPath(): void
    {
        $this->artisan('osdd:resource', ['name' => 'UserResource', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/src/Http/Resources/UserResource.php');
    }

    public function testItGeneratesResourceWithCorrectNamespace(): void
    {
        $this->artisan('osdd:resource', ['name' => 'UserResource', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace Functional\TestLayer\Http\Resources;',
            'class UserResource extends JsonResource',
        ], 'functional/test-layer/src/Http/Resources/UserResource.php');
    }

    public function testItPromptsForLayerWhenNotProvided(): void
    {
        $this->artisan('osdd:resource', ['name' => 'UserResource'])
            ->expectsSearch('Which layer should this be generated in?', 'functional/test-layer', '', ['functional/test-layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/src/Http/Resources/UserResource.php');
    }
}
