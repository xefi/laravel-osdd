<?php

namespace Xefi\LaravelOSDD\Tests\Console\Commands\Make;

use Orchestra\Testbench\Concerns\InteractsWithPublishedFiles;
use Xefi\LaravelOSDD\Tests\TestCase;

class ClassMakeCommandTest extends TestCase
{
    use InteractsWithPublishedFiles;

    protected function tearDown(): void
    {
        $this->app['files']->delete($this->app->basePath('functional/test-layer/src/PaymentService.php'));
        $this->app['files']->deleteDirectory($this->app->basePath('functional/test-layer/src/Services'));

        parent::tearDown();
    }

    public function testItGeneratesClassFileInCorrectPath(): void
    {
        $this->artisan('osdd:class', ['name' => 'PaymentService', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/src/PaymentService.php');
    }

    public function testItGeneratesClassWithCorrectNamespace(): void
    {
        $this->artisan('osdd:class', ['name' => 'PaymentService', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace Functional\TestLayer;',
            'class PaymentService',
        ], 'functional/test-layer/src/PaymentService.php');
    }

    public function testItPromptsForLayerWhenNotProvided(): void
    {
        $this->artisan('osdd:class', ['name' => 'PaymentService'])
            ->expectsSearch('Which layer should this be generated in?', 'functional/test-layer', '', ['functional/test-layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/src/PaymentService.php');
    }

    public function testItGeneratesNestedClassInCorrectPath(): void
    {
        $this->artisan('osdd:class', ['name' => 'Services/PaymentService', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/src/Services/PaymentService.php');
    }

    public function testItGeneratesNestedClassWithCorrectNamespace(): void
    {
        $this->artisan('osdd:class', ['name' => 'Services/PaymentService', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace Functional\TestLayer\Services;',
            'class PaymentService',
        ], 'functional/test-layer/src/Services/PaymentService.php');
    }
}
