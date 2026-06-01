<?php

namespace Xefi\LaravelOSDD\Tests;

use Illuminate\Filesystem\Filesystem;

class IdeHelperModelLocationsTest extends TestCase
{
    private string $tmpPath;

    protected function getEnvironmentSetUp($app): void
    {
        $_SERVER['argv'][] = 'ide-helper:models';

        $this->tmpPath = sys_get_temp_dir() . '/osdd-ide-helper-test-' . uniqid();

        mkdir($this->tmpPath . '/functional/billing/src/Models', 0755, true);

        file_put_contents($this->tmpPath . '/functional/billing/composer.json', json_encode([
            'name'     => 'functional/billing',
            'type'     => 'layer',
            'autoload' => ['psr-4' => ['Functional\\Billing\\' => 'src/']],
        ]));

        $app->setBasePath($this->tmpPath);
        $app['config']->set('osdd.layers.paths', [
            'functional' => $this->tmpPath . '/functional',
        ]);

        // Simulate barryvdh/laravel-ide-helper being installed with its default config.
        $app['config']->set('ide-helper.model_locations', ['app']);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        (new Filesystem)->deleteDirectory($this->tmpPath);

        $_SERVER['argv'] = array_filter($_SERVER['argv'], fn($v) => $v !== 'ide-helper:models');
    }

    public function testLayerSourcePathIsAddedToModelLocations(): void
    {
        $locations = $this->app['config']->get('ide-helper.model_locations', []);

        $this->assertContains($this->tmpPath . '/functional/billing/src', $locations);
    }

    public function testExistingModelLocationsArePreserved(): void
    {
        $locations = $this->app['config']->get('ide-helper.model_locations', []);

        $this->assertContains('app', $locations);
    }
}
