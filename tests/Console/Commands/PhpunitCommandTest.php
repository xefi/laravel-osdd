<?php

namespace Xefi\LaravelOSDD\Tests\Console\Commands;

use Xefi\LaravelOSDD\Tests\TestCase;

class PhpunitCommandTest extends TestCase
{
    private string $xmlPath;

    private string $originalXml = <<<'XML'
        <?xml version="1.0" encoding="UTF-8"?>
        <phpunit>
            <testsuites>
                <testsuite name="Unit">
                    <directory>tests</directory>
                </testsuite>
            </testsuites>
        </phpunit>
        XML;

    protected function setUp(): void
    {
        parent::setUp();

        $this->xmlPath = $this->app->basePath('phpunit.xml');
        $this->app['files']->put($this->xmlPath, $this->originalXml);

        $this->app['files']->makeDirectory(
            $this->app->basePath('functional/test-layer/tests/Feature'),
            0755,
            true,
            true
        );
    }

    protected function tearDown(): void
    {
        $this->app['files']->put($this->xmlPath, $this->originalXml);
        $this->app['files']->deleteDirectory($this->app->basePath('functional/test-layer/tests'));

        parent::tearDown();
    }

    public function testItAddsTestsuiteForDiscoveredLayers(): void
    {
        $this->artisan('osdd:phpunit')->assertExitCode(0);

        $xml = $this->app['files']->get($this->xmlPath);
        $dom = new \DOMDocument();
        $dom->loadXML($xml);

        $xpath = new \DOMXPath($dom);
        $suite = $xpath->query('//testsuites/testsuite[@name="functional/test-layer"]')->item(0);

        $this->assertNotNull($suite);
        $this->assertStringContainsString(
            'functional/test-layer/tests',
            $suite->getElementsByTagName('directory')->item(0)->nodeValue
        );
    }

    public function testItSkipsAlreadyPresentTestsuites(): void
    {
        $this->artisan('osdd:phpunit')->assertExitCode(0);

        $xmlAfterFirst = $this->app['files']->get($this->xmlPath);

        $this->artisan('osdd:phpunit')->assertExitCode(0);

        $this->assertSame($xmlAfterFirst, $this->app['files']->get($this->xmlPath));
    }

    public function testItSkipsLayersWithoutTestsDirectory(): void
    {
        $layerPath = $this->app->basePath('functional/no-tests-layer');
        $this->app['files']->makeDirectory($layerPath, 0755, true, true);
        $this->app['files']->put($layerPath . '/composer.json', json_encode([
            'name' => 'functional/no-tests-layer',
            'type' => 'layer',
            'autoload' => ['psr-4' => ['Functional\\NoTestsLayer\\' => 'src/']],
        ]));

        $this->artisan('osdd:phpunit')->assertExitCode(0);

        $xml = $this->app['files']->get($this->xmlPath);
        $dom = new \DOMDocument();
        $dom->loadXML($xml);

        $xpath = new \DOMXPath($dom);
        $suite = $xpath->query('//testsuites/testsuite[@name="functional/no-tests-layer"]')->item(0);

        $this->assertNull($suite);

        $this->app['files']->deleteDirectory($layerPath);
    }

    public function testItFailsWhenPhpunitXmlIsMissing(): void
    {
        $this->app['files']->delete($this->xmlPath);

        $this->artisan('osdd:phpunit')->assertExitCode(1);
    }
}
