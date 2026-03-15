<?php

namespace Xefi\LaravelOSDD\Tests\Console\Commands\Make;

use Orchestra\Testbench\Concerns\InteractsWithPublishedFiles;
use Xefi\LaravelOSDD\Tests\TestCase;

class ControllerMakeCommandTest extends TestCase
{
    use InteractsWithPublishedFiles;

    protected function tearDown(): void
    {
        $this->app['files']->deleteDirectory($this->app->basePath('functional/test-layer/src/Http'));

        parent::tearDown();
    }

    public function testItGeneratesControllerFileInCorrectPath(): void
    {
        $this->artisan('osdd:controller', ['name' => 'UserController', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/src/Http/Controllers/UserController.php');
    }

    public function testItGeneratesControllerWithCorrectNamespace(): void
    {
        $this->artisan('osdd:controller', ['name' => 'UserController', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace Functional\TestLayer\Http\Controllers;',
            'class UserController',
        ], 'functional/test-layer/src/Http/Controllers/UserController.php');
    }

    public function testItPromptsForLayerWhenNotProvided(): void
    {
        $this->artisan('osdd:controller', ['name' => 'UserController', '--resource' => true])
            ->expectsSearch('Which layer should this be generated in?', 'functional/test-layer', '', ['functional/test-layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/src/Http/Controllers/UserController.php');
    }

    public function testItGeneratesNestedControllerInCorrectPath(): void
    {
        $this->artisan('osdd:controller', ['name' => 'Admin/AdminController', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/src/Http/Controllers/Admin/AdminController.php');
    }

    public function testItGeneratesNestedControllerWithCorrectNamespace(): void
    {
        $this->artisan('osdd:controller', ['name' => 'Admin/AdminController', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace Functional\TestLayer\Http\Controllers\Admin;',
            'class AdminController',
        ], 'functional/test-layer/src/Http/Controllers/Admin/AdminController.php');
    }

    public function testItGeneratesResourceController(): void
    {
        $this->artisan('osdd:controller', ['name' => 'UserController', '--resource' => true, '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'public function index()',
            'public function create()',
            'public function store(',
            'public function show(',
            'public function edit(',
            'public function update(',
            'public function destroy(',
        ], 'functional/test-layer/src/Http/Controllers/UserController.php');
    }

    public function testItGeneratesResourceControllerWithFormRequests(): void
    {
        $this->artisan('osdd:controller', ['name' => 'UserController', '--resource' => true, '--model' => 'User', '--requests' => true, '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/src/Http/Controllers/UserController.php');
        $this->assertFilenameExists('functional/test-layer/src/Http/Requests/StoreUserRequest.php');
        $this->assertFilenameExists('functional/test-layer/src/Http/Requests/UpdateUserRequest.php');

        $this->assertFileContains([
            'use Functional\TestLayer\Http\Requests\StoreUserRequest;',
            'use Functional\TestLayer\Http\Requests\UpdateUserRequest;',
        ], 'functional/test-layer/src/Http/Controllers/UserController.php');
    }
}
