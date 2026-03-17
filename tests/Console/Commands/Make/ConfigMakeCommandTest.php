<?php

namespace Xefi\LaravelOSDD\Tests\Console\Commands\Make;

use Orchestra\Testbench\Concerns\InteractsWithPublishedFiles;
use Xefi\LaravelOSDD\Tests\TestCase;

class ConfigMakeCommandTest extends TestCase
{
    use InteractsWithPublishedFiles;

    protected function tearDown(): void
    {
        $this->app['files']->deleteDirectory($this->app->basePath('functional/test-layer/config'));

        parent::tearDown();
    }

    public function testItGeneratesConfigFileInCorrectPath(): void
    {
        $this->artisan('osdd:config', ['name' => 'payments', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/config/payments.php');
    }

    public function testItAppendsPhpExtensionWhenMissing(): void
    {
        $this->artisan('osdd:config', ['name' => 'payments', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/config/payments.php');
        $this->assertFilenameNotExists('functional/test-layer/config/payments.php.php');
    }

    public function testItPromptsForLayerWhenNotProvided(): void
    {
        $this->artisan('osdd:config', ['name' => 'payments'])
            ->expectsSearch('Which layer should this be generated in?', 'functional/test-layer', '', ['functional/test-layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/config/payments.php');
    }
}
