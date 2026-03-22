<?php

namespace Xefi\LaravelOSDD\Tests\Console\Commands\Make;

use Orchestra\Testbench\Concerns\InteractsWithPublishedFiles;
use Xefi\LaravelOSDD\Tests\TestCase;

class PolicyMakeCommandTest extends TestCase
{
    use InteractsWithPublishedFiles;

    protected function tearDown(): void
    {
        $this->app['files']->deleteDirectory($this->app->basePath('functional/test-layer/src/Policies'));

        parent::tearDown();
    }

    public function testItGeneratesPolicyFileInCorrectPath(): void
    {
        $this->artisan('osdd:policy', ['name' => 'UserPolicy', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/src/Policies/UserPolicy.php');
    }

    public function testItGeneratesPolicyWithCorrectNamespace(): void
    {
        $this->artisan('osdd:policy', ['name' => 'UserPolicy', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace Functional\TestLayer\Policies;',
            'class UserPolicy',
        ], 'functional/test-layer/src/Policies/UserPolicy.php');
    }

    public function testItPromptsForLayerWhenNotProvided(): void
    {
        $this->artisan('osdd:policy', ['name' => 'UserPolicy'])
            ->expectsSearch('Which layer should this be generated in?', 'functional/test-layer', '', ['functional/test-layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/src/Policies/UserPolicy.php');
    }

    public function testItGeneratesNestedPolicyInCorrectPath(): void
    {
        $this->artisan('osdd:policy', ['name' => 'Admin/AdminPolicy', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/src/Policies/Admin/AdminPolicy.php');
    }

    public function testItGeneratesNestedPolicyWithCorrectNamespace(): void
    {
        $this->artisan('osdd:policy', ['name' => 'Admin/AdminPolicy', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace Functional\TestLayer\Policies\Admin;',
            'class AdminPolicy',
        ], 'functional/test-layer/src/Policies/Admin/AdminPolicy.php');
    }

    public function testItGeneratesPolicyWithModelMethods(): void
    {
        $this->artisan('osdd:policy', ['name' => 'UserPolicy', '--layer' => 'functional/test-layer', '--model' => 'User'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'public function viewAny(',
            'public function view(',
            'public function create(',
            'public function update(',
            'public function delete(',
        ], 'functional/test-layer/src/Policies/UserPolicy.php');
    }

    public function testItQualifiesModelWithModelsSubNamespace(): void
    {
        $this->artisan('osdd:policy', ['name' => 'UserPolicy', '--layer' => 'functional/test-layer', '--model' => 'User'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'use Functional\TestLayer\Models\User;',
        ], 'functional/test-layer/src/Policies/UserPolicy.php');
    }

    public function testItReplacesUserModelPlaceholderInPolicyStub(): void
    {
        $this->artisan('osdd:policy', ['name' => 'UserPolicy', '--layer' => 'functional/test-layer', '--model' => 'User'])
            ->assertExitCode(0);

        $contents = $this->app['files']->get(
            $this->app->basePath('functional/test-layer/src/Policies/UserPolicy.php')
        );

        $this->assertStringNotContainsString('{{ namespacedUserModel }}', $contents);
        $this->assertStringNotContainsString('{{namespacedUserModel}}', $contents);
    }
}
