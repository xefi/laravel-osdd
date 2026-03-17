<?php

namespace Xefi\LaravelOSDD\Tests\Console\Commands\Make;

use Orchestra\Testbench\Concerns\InteractsWithPublishedFiles;
use Xefi\LaravelOSDD\Tests\TestCase;

class ExceptionMakeCommandTest extends TestCase
{
    use InteractsWithPublishedFiles;

    protected function tearDown(): void
    {
        $this->app['files']->deleteDirectory($this->app->basePath('functional/test-layer/src/Exceptions'));

        parent::tearDown();
    }

    public function testItGeneratesExceptionFileInCorrectPath(): void
    {
        $this->artisan('osdd:exception', ['name' => 'UserNotFoundException', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/src/Exceptions/UserNotFoundException.php');
    }

    public function testItGeneratesExceptionWithCorrectNamespace(): void
    {
        $this->artisan('osdd:exception', ['name' => 'UserNotFoundException', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace Functional\TestLayer\Exceptions;',
            'class UserNotFoundException extends Exception',
        ], 'functional/test-layer/src/Exceptions/UserNotFoundException.php');
    }

    public function testItPromptsForLayerWhenNotProvided(): void
    {
        $this->artisan('osdd:exception', ['name' => 'UserNotFoundException'])
            ->expectsSearch('Which layer should this be generated in?', 'functional/test-layer', '', ['functional/test-layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/src/Exceptions/UserNotFoundException.php');
    }

    public function testItGeneratesNestedExceptionInCorrectPath(): void
    {
        $this->artisan('osdd:exception', ['name' => 'Auth/InvalidTokenException', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/src/Exceptions/Auth/InvalidTokenException.php');
    }

    public function testItGeneratesNestedExceptionWithCorrectNamespace(): void
    {
        $this->artisan('osdd:exception', ['name' => 'Auth/InvalidTokenException', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace Functional\TestLayer\Exceptions\Auth;',
            'class InvalidTokenException extends Exception',
        ], 'functional/test-layer/src/Exceptions/Auth/InvalidTokenException.php');
    }
}
