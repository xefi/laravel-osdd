<?php

namespace Xefi\LaravelOSDD\Tests\Console\Commands\Make;

use Orchestra\Testbench\Concerns\InteractsWithPublishedFiles;
use Xefi\LaravelOSDD\Tests\TestCase;

class ConfigMakeCommandTest extends TestCase
{
    use InteractsWithPublishedFiles;

    private string $providerPath;

    private string $providerOriginal;

    protected function setUp(): void
    {
        parent::setUp();

        $this->providerPath = $this->app->basePath('functional/test-layer/src/Providers/TestLayerServiceProvider.php');

        $this->app['files']->ensureDirectoryExists(dirname($this->providerPath));

        $this->providerOriginal = <<<'PHP'
<?php

namespace Functional\TestLayer\Providers;

use Xefi\LaravelOSDD\LayerServiceProvider;

class TestLayerServiceProvider extends LayerServiceProvider
{
    public function boot(): void
    {
        //
    }

    public function register(): void
    {
        //
    }
}
PHP;

        file_put_contents($this->providerPath, $this->providerOriginal);
    }

    protected function tearDown(): void
    {
        $this->app['files']->deleteDirectory($this->app->basePath('functional/test-layer/config'));
        $this->app['files']->deleteDirectory($this->app->basePath('functional/test-layer/src/Providers'));

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

    public function testItInjectsOverrideConfigFromIntoServiceProvider(): void
    {
        $this->artisan('osdd:config', ['name' => 'payments', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFileContains(
            ["\$this->overrideConfigFrom(__DIR__ . '/../../config/payments.php', 'payments');"],
            'functional/test-layer/src/Providers/TestLayerServiceProvider.php',
        );
    }

    public function testInjectionIsIdempotent(): void
    {
        $this->artisan('osdd:config', ['name' => 'payments', '--layer' => 'functional/test-layer', '--force' => true]);
        $this->artisan('osdd:config', ['name' => 'payments', '--layer' => 'functional/test-layer', '--force' => true]);

        $content = file_get_contents($this->providerPath);
        $count   = substr_count($content, "overrideConfigFrom(__DIR__ . '/../../config/payments.php'");

        $this->assertSame(1, $count);
    }

    public function testItSkipsInjectionWhenServiceProviderDoesNotExist(): void
    {
        $this->app['files']->delete($this->providerPath);

        $this->artisan('osdd:config', ['name' => 'payments', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        // Config file still generated; no crash without a provider
        $this->assertFilenameExists('functional/test-layer/config/payments.php');
    }
}
