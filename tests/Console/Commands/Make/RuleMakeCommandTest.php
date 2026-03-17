<?php

namespace Xefi\LaravelOSDD\Tests\Console\Commands\Make;

use Orchestra\Testbench\Concerns\InteractsWithPublishedFiles;
use Xefi\LaravelOSDD\Tests\TestCase;

class RuleMakeCommandTest extends TestCase
{
    use InteractsWithPublishedFiles;

    protected function tearDown(): void
    {
        $this->app['files']->deleteDirectory($this->app->basePath('functional/test-layer/src/Rules'));

        parent::tearDown();
    }

    public function testItGeneratesRuleFileInCorrectPath(): void
    {
        $this->artisan('osdd:rule', ['name' => 'Uppercase', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/src/Rules/Uppercase.php');
    }

    public function testItGeneratesRuleWithCorrectNamespace(): void
    {
        $this->artisan('osdd:rule', ['name' => 'Uppercase', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace Functional\TestLayer\Rules;',
            'class Uppercase implements ValidationRule',
        ], 'functional/test-layer/src/Rules/Uppercase.php');
    }

    public function testItPromptsForLayerWhenNotProvided(): void
    {
        $this->artisan('osdd:rule', ['name' => 'Uppercase'])
            ->expectsSearch('Which layer should this be generated in?', 'functional/test-layer', '', ['functional/test-layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/src/Rules/Uppercase.php');
    }
}
