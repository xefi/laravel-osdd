<?php

namespace Xefi\LaravelOSDD\Tests\Console\Commands\Make;

use Orchestra\Testbench\Concerns\InteractsWithPublishedFiles;
use Xefi\LaravelOSDD\Tests\TestCase;

class InterfaceMakeCommandTest extends TestCase
{
    use InteractsWithPublishedFiles;

    protected function tearDown(): void
    {
        $this->app['files']->deleteDirectory($this->app->basePath('functional/test-layer/src/Contracts'));

        parent::tearDown();
    }

    public function testItGeneratesInterfaceFileInCorrectPath(): void
    {
        $this->artisan('osdd:interface', ['name' => 'PaymentGateway', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/src/Contracts/PaymentGateway.php');
    }

    public function testItGeneratesInterfaceWithCorrectNamespace(): void
    {
        $this->artisan('osdd:interface', ['name' => 'PaymentGateway', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace Functional\TestLayer\Contracts;',
            'interface PaymentGateway',
        ], 'functional/test-layer/src/Contracts/PaymentGateway.php');
    }

    public function testItPromptsForLayerWhenNotProvided(): void
    {
        $this->artisan('osdd:interface', ['name' => 'PaymentGateway'])
            ->expectsSearch('Which layer should this be generated in?', 'functional/test-layer', '', ['functional/test-layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/src/Contracts/PaymentGateway.php');
    }
}
