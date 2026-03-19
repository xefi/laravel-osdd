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
        };
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

}
