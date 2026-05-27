<?php

namespace Xefi\LaravelOSDD\Tests;

use Illuminate\Filesystem\Filesystem;
use Xefi\LaravelOSDD\LayerServiceProvider;

class LayerServiceProviderTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tmpDir = sys_get_temp_dir() . '/osdd-config-test-' . uniqid();
        mkdir($this->tmpDir, 0755, true);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        (new Filesystem)->deleteDirectory($this->tmpDir);
    }

    private function writeConfig(string $filename, array $values): string
    {
        $path = $this->tmpDir . '/' . $filename;
        file_put_contents($path, '<?php return ' . var_export($values, true) . ';');

        return $path;
    }

    private function makeProvider(): LayerServiceProvider
    {
        return new class($this->app) extends LayerServiceProvider {
            public function boot(): void {}
            public function register(): void {}
            public function callOverrideConfigFrom(string $path, string $key): void
            {
                $this->overrideConfigFrom($path, $key);
            }
            public function callWithRouting(
                array|string|null $web = null,
                array|string|null $api = null,
                ?string $commands = null,
                ?string $channels = null,
                ?string $health = null,
                string $apiPrefix = 'api',
            ): void {
                $this->withRouting(
                    web: $web,
                    api: $api,
                    commands: $commands,
                    channels: $channels,
                    health: $health,
                    apiPrefix: $apiPrefix,
                );
            }
        };
    }

    private function writeRouteFile(string $filename, string $contents): string
    {
        $path = $this->tmpDir . '/' . $filename;
        file_put_contents($path, $contents);

        return $path;
    }

    public function testItOverridesAnExistingConfigKey(): void
    {
        $this->app['config']->set('services.stripe.key', 'original-key');

        $path = $this->writeConfig('services.php', ['stripe' => ['key' => 'layer-key']]);

        $provider = $this->makeProvider();
        $provider->callOverrideConfigFrom($path, 'services');

        $this->app->boot();

        $this->assertSame('layer-key', $this->app['config']->get('services.stripe.key'));
    }

    public function testItPreservesExistingKeysNotPresentInTheOverride(): void
    {
        $this->app['config']->set('services', [
            'stripe' => ['key' => 'original-key', 'secret' => 'original-secret'],
        ]);

        $path = $this->writeConfig('services.php', ['stripe' => ['key' => 'layer-key']]);

        $provider = $this->makeProvider();
        $provider->callOverrideConfigFrom($path, 'services');

        $this->app->boot();

        $this->assertSame('layer-key', $this->app['config']->get('services.stripe.key'));
        $this->assertSame('original-secret', $this->app['config']->get('services.stripe.secret'));
    }

    public function testItMergesDeepNestedKeysRecursively(): void
    {
        $this->app['config']->set('package', [
            'section' => ['a' => 1, 'b' => 2],
            'other'   => 'untouched',
        ]);

        $path = $this->writeConfig('package.php', ['section' => ['b' => 99, 'c' => 3]]);

        $provider = $this->makeProvider();
        $provider->callOverrideConfigFrom($path, 'package');

        $this->app->boot();

        $this->assertSame(1, $this->app['config']->get('package.section.a'));
        $this->assertSame(99, $this->app['config']->get('package.section.b'));
        $this->assertSame(3, $this->app['config']->get('package.section.c'));
        $this->assertSame('untouched', $this->app['config']->get('package.other'));
    }

    public function testItCreatesTheKeyWhenItDidNotExistBefore(): void
    {
        $path = $this->writeConfig('brand-new.php', ['option' => 'value']);

        $provider = $this->makeProvider();
        $provider->callOverrideConfigFrom($path, 'brand-new');

        $this->app->boot();

        $this->assertSame('value', $this->app['config']->get('brand-new.option'));
    }

    public function testLayerAliasesAreNotRegisteredOutsideOfTinker(): void
    {
        // tinker is never in argv in this test class — aliases must stay empty
        $this->assertNotContains('Functional\\TestLayer\\', $this->app['config']->get('tinker.alias', []));
    }

    public function testWithRoutingLoadsWebRoutesInsideTheWebMiddlewareGroup(): void
    {
        $path = $this->writeRouteFile(
            'web.php',
            "<?php \\Illuminate\\Support\\Facades\\Route::get('/dashboard', fn() => 'ok')->name('web.dashboard');\n",
        );

        $this->makeProvider()->callWithRouting(web: $path);

        $route = $this->findRouteByName('web.dashboard');

        $this->assertNotNull($route);
        $this->assertSame('dashboard', $route->uri());
        $this->assertSame(['web'], $route->middleware());
    }

    public function testWithRoutingLoadsApiRoutesWithApiPrefixAndMiddleware(): void
    {
        $path = $this->writeRouteFile(
            'api.php',
            "<?php \\Illuminate\\Support\\Facades\\Route::get('/things', fn() => 'ok')->name('api.things');\n",
        );

        $this->makeProvider()->callWithRouting(api: $path);

        $route = $this->findRouteByName('api.things');

        $this->assertNotNull($route);
        $this->assertSame('api/things', $route->uri());
        $this->assertSame(['api'], $route->middleware());
    }

    public function testWithRoutingHonoursACustomApiPrefix(): void
    {
        $path = $this->writeRouteFile(
            'api.php',
            "<?php \\Illuminate\\Support\\Facades\\Route::get('/things', fn() => 'ok')->name('api.v2.things');\n",
        );

        $this->makeProvider()->callWithRouting(api: $path, apiPrefix: 'api/v2');

        $route = $this->findRouteByName('api.v2.things');

        $this->assertNotNull($route);
        $this->assertSame('api/v2/things', $route->uri());
    }

    public function testWithRoutingAcceptsMultipleFilesPerType(): void
    {
        $a = $this->writeRouteFile('a.php', "<?php \\Illuminate\\Support\\Facades\\Route::get('/a', fn() => 'a')->name('a');\n");
        $b = $this->writeRouteFile('b.php', "<?php \\Illuminate\\Support\\Facades\\Route::get('/b', fn() => 'b')->name('b');\n");

        $this->makeProvider()->callWithRouting(web: [$a, $b]);

        $this->assertNotNull($this->findRouteByName('a'));
        $this->assertNotNull($this->findRouteByName('b'));
    }

    public function testWithRoutingRegistersAHealthEndpoint(): void
    {
        $this->makeProvider()->callWithRouting(health: '/up');

        $response = $this->get('/up');

        $response->assertOk();
        $response->assertJson(['status' => 'ok']);
    }

    public function testWithRoutingRequiresChannelsFileSoBroadcastChannelsAreRegistered(): void
    {
        $marker = $this->tmpDir . '/channels-loaded';
        $path = $this->writeRouteFile('channels.php', "<?php file_put_contents('{$marker}', 'yes');\n");

        $this->makeProvider()->callWithRouting(channels: $path);

        $this->assertFileExists($marker);
    }

    public function testWithRoutingLoadsConsoleFileWhenRunningInConsole(): void
    {
        // Testbench runs PHPUnit through Artisan, so runningInConsole() is true here.
        $marker = $this->tmpDir . '/console-loaded';
        $path = $this->writeRouteFile('console.php', "<?php file_put_contents('{$marker}', 'yes');\n");

        $this->makeProvider()->callWithRouting(commands: $path);

        $this->assertFileExists($marker);
    }

    public function testWithRoutingSilentlySkipsMissingFiles(): void
    {
        $this->makeProvider()->callWithRouting(
            web: $this->tmpDir . '/missing-web.php',
            api: $this->tmpDir . '/missing-api.php',
            commands: $this->tmpDir . '/missing-console.php',
            channels: $this->tmpDir . '/missing-channels.php',
        );

        $this->assertNull($this->findRouteByName('web.dashboard'));
    }

    private function findRouteByName(string $name): ?\Illuminate\Routing\Route
    {
        foreach ($this->app['router']->getRoutes()->getRoutes() as $route) {
            if ($route->getName() === $name) {
                return $route;
            }
        }
        return null;
    }
}
