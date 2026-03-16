<?php

namespace Xefi\LaravelOSDD\Console\Commands;

use DOMDocument;
use DOMXPath;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;
use Xefi\LaravelOSDD\Layers\LayersCollection;

#[AsCommand(name: 'osdd:phpunit')]
class PhpunitCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'osdd:phpunit';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize phpunit.xml testsuites with discovered OSDD layers';

    public function handle(): int
    {
        $xmlPath = $this->laravel->basePath('phpunit.xml');

        if (!$this->laravel['files']->exists($xmlPath)) {
            $this->error('phpunit.xml not found at ' . $xmlPath);

            return self::FAILURE;
        }

        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->load($xmlPath);

        $xpath = new DOMXPath($dom);
        $testSuites = $xpath->query('//testsuites')->item(0);

        if (!$testSuites) {
            $this->error('<testsuites> element not found in phpunit.xml.');

            return self::FAILURE;
        }

        $layers = LayersCollection::fromConfig();

        if ($layers->isEmpty()) {
            $this->warn('No OSDD layers discovered.');

            return self::SUCCESS;
        }

        $basePath = rtrim($this->laravel->basePath(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $added = 0;

        foreach ($layers as $layer) {
            $layerName = $layer->manifest->name();

            $existing = $xpath->query('//testsuites/testsuite[@name="' . $layerName . '"]')->item(0);

            if ($existing) {
                $this->line("Testsuite <comment>{$layerName}</comment> already present, skipping.");
                continue;
            }

            $relativeTestDir = Str::replaceFirst($basePath, '', $layer->path . '/tests');
            $relativeTestDir = str_replace('\\', '/', $relativeTestDir);

            $suite = $dom->createElement('testsuite');
            $suite->setAttribute('name', $layerName);
            $suite->appendChild($dom->createElement('directory', $relativeTestDir));
            $testSuites->appendChild($suite);

            $this->info("Added testsuite <comment>{$layerName}</comment> → {$relativeTestDir}");
            $added++;
        }

        if ($added > 0) {
            $this->laravel['files']->put($xmlPath, $dom->saveXML());
            $this->info('phpunit.xml updated successfully.');
        }

        return self::SUCCESS;
    }
}
