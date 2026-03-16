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
    }

    protected function tearDown(): void
    {
        $this->app['files']->put($this->xmlPath, $this->originalXml);

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

    public function testItFailsWhenPhpunitXmlIsMissing(): void
    {
        $this->app['files']->delete($this->xmlPath);

        $this->artisan('osdd:phpunit')->assertExitCode(1);
    }
}
