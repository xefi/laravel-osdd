<?php

namespace Xefi\LaravelOSDD\Tests;

use Illuminate\Support\ServiceProvider;
use Xefi\LaravelOSDD\LayerServiceProvider;

/**
 * Simulates a third-party package (e.g. Laravel Horizon) that merges its
 * defaults in register() and then reads that config during boot() — the way
 * Horizon reads horizon.path / middleware when registering its routes.
 */
class FakeThirdPartyProvider extends ServiceProvider
{
    public static ?string $pathSeenDuringBoot = null;

    public function register(): void
    {
        $path = sys_get_temp_dir() . '/osdd-3p-default.php';
        file_put_contents($path, "<?php return ['path' => 'horizon', 'use' => 'redis'];");

        $this->mergeConfigFrom($path, 'horizon');
    }

    public function boot(): void
    {
        self::$pathSeenDuringBoot = $this->app['config']->get('horizon.path');
    }
}

/** A layer shipping config/horizon.php that should override the package's defaults. */
class FakeLayerProvider extends LayerServiceProvider
{
    public function register(): void
    {
        $path = sys_get_temp_dir() . '/osdd-3p-layer.php';
        file_put_contents($path, "<?php return ['path' => 'my-horizon'];");

        $this->overrideConfigFrom($path, 'horizon');
    }

    public function boot(): void
    {
        //
    }
}

class LayerConfigOverrideTest extends TestCase
{
    public static array $providerOrder = [];

    protected function getPackageProviders($app): array
    {
        return static::$providerOrder;
    }

    protected function tearDown(): void
    {
        FakeThirdPartyProvider::$pathSeenDuringBoot = null;
        parent::tearDown();
    }

    public function testLayerOverridesThirdPartyDefaultsAndSurvivesGaps(): void
    {
        static::$providerOrder = [FakeLayerProvider::class, FakeThirdPartyProvider::class];
        $this->refreshApplication();

        // Layer wins where it speaks...
        $this->assertSame('my-horizon', $this->app['config']->get('horizon.path'));
        // ...the package default survives where the layer is silent.
        $this->assertSame('redis', $this->app['config']->get('horizon.use'));
    }

    public function testLayerOverrideIsVisibleWhileThirdPartyBoots(): void
    {
        // The reported bug: Horizon read its config during boot() and saw the
        // default, because overrideConfigFrom() used to defer to app->booted().
        static::$providerOrder = [FakeLayerProvider::class, FakeThirdPartyProvider::class];
        $this->refreshApplication();

        $this->assertSame('my-horizon', FakeThirdPartyProvider::$pathSeenDuringBoot);
    }

    public function testLayerWinsRegardlessOfProviderRegistrationOrder(): void
    {
        // Same expectation when the third-party package registers/boots first.
        static::$providerOrder = [FakeThirdPartyProvider::class, FakeLayerProvider::class];
        $this->refreshApplication();

        $this->assertSame('my-horizon', $this->app['config']->get('horizon.path'));
        $this->assertSame('my-horizon', FakeThirdPartyProvider::$pathSeenDuringBoot);
        $this->assertSame('redis', $this->app['config']->get('horizon.use'));
    }
}
