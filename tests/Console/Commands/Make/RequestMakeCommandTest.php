<?php

namespace Xefi\LaravelOSDD\Tests\Console\Commands\Make;

use Orchestra\Testbench\Concerns\InteractsWithPublishedFiles;
use Xefi\LaravelOSDD\Tests\TestCase;

class RequestMakeCommandTest extends TestCase
{
    use InteractsWithPublishedFiles;

    protected function tearDown(): void
    {
        $this->app['files']->deleteDirectory($this->app->basePath('functional/test-layer/src/Http'));

        parent::tearDown();
    }

    public function testItGeneratesRequestFileInCorrectPath(): void
    {
        $this->artisan('osdd:request', ['name' => 'StoreUserRequest', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/src/Http/Requests/StoreUserRequest.php');
    }

    public function testItGeneratesRequestWithCorrectNamespace(): void
    {
        $this->artisan('osdd:request', ['name' => 'StoreUserRequest', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace Functional\TestLayer\Http\Requests;',
            'class StoreUserRequest extends FormRequest',
        ], 'functional/test-layer/src/Http/Requests/StoreUserRequest.php');
    }

    public function testItPromptsForLayerWhenNotProvided(): void
    {
        $this->artisan('osdd:request', ['name' => 'StoreUserRequest'])
            ->expectsSearch('Which layer should this be generated in?', 'functional/test-layer', '', ['functional/test-layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/src/Http/Requests/StoreUserRequest.php');
    }

    public function testItGeneratesNestedRequestInCorrectPath(): void
    {
        $this->artisan('osdd:request', ['name' => 'Admin/StoreAdminRequest', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/src/Http/Requests/Admin/StoreAdminRequest.php');
    }

    public function testItGeneratesNestedRequestWithCorrectNamespace(): void
    {
        $this->artisan('osdd:request', ['name' => 'Admin/StoreAdminRequest', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace Functional\TestLayer\Http\Requests\Admin;',
            'class StoreAdminRequest extends FormRequest',
        ], 'functional/test-layer/src/Http/Requests/Admin/StoreAdminRequest.php');
    }
}
