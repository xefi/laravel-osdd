<?php

namespace Xefi\LaravelOSDD\Tests\Console\Commands\Make;

use Orchestra\Testbench\Concerns\InteractsWithPublishedFiles;
use Xefi\LaravelOSDD\Tests\TestCase;

class TestMakeCommandTest extends TestCase
{
    use InteractsWithPublishedFiles;

    protected function tearDown(): void
    {
        $this->app['files']->deleteDirectory($this->app->basePath('functional/test-layer/tests'));

        parent::tearDown();
    }

    public function testItGeneratesUnitTestFileInCorrectPath(): void
    {
        $this->artisan('osdd:test', ['name' => 'UserTest', '--layer' => 'functional/test-layer', '--unit' => true])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/tests/Unit/UserTest.php');
    }

    public function testItGeneratesUnitTestWithCorrectNamespace(): void
    {
        $this->artisan('osdd:test', ['name' => 'UserTest', '--layer' => 'functional/test-layer', '--unit' => true])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace Functional\TestLayer\Tests\Unit;',
            'class UserTest',
        ], 'functional/test-layer/tests/Unit/UserTest.php');
    }

    public function testItGeneratesFeatureTestFileInCorrectPath(): void
    {
        $this->artisan('osdd:test', ['name' => 'UserTest', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/tests/Feature/UserTest.php');
    }

    public function testItGeneratesFeatureTestWithCorrectNamespace(): void
    {
        $this->artisan('osdd:test', ['name' => 'UserTest', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace Functional\TestLayer\Tests\Feature;',
            'class UserTest',
        ], 'functional/test-layer/tests/Feature/UserTest.php');
    }

    public function testItPromptsForLayerWhenNotProvided(): void
    {
        $this->artisan('osdd:test', ['name' => 'UserTest', '--unit' => true])
            ->expectsSearch('Which layer should this be generated in?', 'functional/test-layer', '', ['functional/test-layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/tests/Unit/UserTest.php');
    }

    public function testItGeneratesNestedUnitTestInCorrectPath(): void
    {
        $this->artisan('osdd:test', ['name' => 'Services/UserServiceTest', '--layer' => 'functional/test-layer', '--unit' => true])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/tests/Unit/Services/UserServiceTest.php');
    }

    public function testItGeneratesNestedUnitTestWithCorrectNamespace(): void
    {
        $this->artisan('osdd:test', ['name' => 'Services/UserServiceTest', '--layer' => 'functional/test-layer', '--unit' => true])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace Functional\TestLayer\Tests\Unit\Services;',
            'class UserServiceTest',
        ], 'functional/test-layer/tests/Unit/Services/UserServiceTest.php');
    }
}
