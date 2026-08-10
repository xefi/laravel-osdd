<?php

namespace Xefi\LaravelOSDD\Tests\Console;

use Illuminate\Contracts\Console\Kernel;
use Symfony\Component\Console\Command\Command;
use Xefi\LaravelOSDD\Tests\TestCase;

class CommandRegistrationTest extends TestCase
{
    private const GENERATOR_COMMANDS = [
        'osdd:cast',
        'osdd:channel',
        'osdd:class',
        'osdd:command',
        'osdd:config',
        'osdd:controller',
        'osdd:enum',
        'osdd:event',
        'osdd:exception',
        'osdd:factory',
        'osdd:interface',
        'osdd:job',
        'osdd:listener',
        'osdd:mail',
        'osdd:middleware',
        'osdd:migration',
        'osdd:model',
        'osdd:notification',
        'osdd:observer',
        'osdd:policy',
        'osdd:provider',
        'osdd:request',
        'osdd:resource',
        'osdd:rule',
        'osdd:scope',
        'osdd:seeder',
        'osdd:test',
        'osdd:trait',
        'osdd:view',
    ];

    private const NON_GENERATOR_COMMANDS = [
        'osdd:layer',
        'osdd:phpunit',
        'osdd:seed',
        'osdd:start',
    ];

    public function testItEnumeratesTheCommandRegistry(): void
    {
        $this->artisan('list')->assertExitCode(0);
    }

    public function testItRegistersEveryOsddCommand(): void
    {
        $expected = array_merge(self::GENERATOR_COMMANDS, self::NON_GENERATOR_COMMANDS);

        sort($expected);

        $this->assertSame($expected, array_keys($this->osddCommands()));
    }

    public function testEveryOsddCommandResolvesUnderTheNameItIsRegisteredWith(): void
    {
        foreach ($this->osddCommands() as $name => $command) {
            $this->assertSame(
                $name,
                $command->getName(),
                "Command '{$name}' resolves as '{$command->getName()}'.",
            );
        }
    }

    public function testEveryOsddGeneratorAcceptsALayerOption(): void
    {
        $commands = $this->osddCommands();

        foreach (self::GENERATOR_COMMANDS as $name) {
            $this->assertTrue(
                $commands[$name]->getDefinition()->hasOption('layer'),
                "Command '{$name}' has no --layer option.",
            );
        }
    }

    public function testOsddGeneratorsKeepTheOptionsInheritedFromTheirLaravelCounterpart(): void
    {
        $commands = $this->osddCommands();

        $this->assertTrue($commands['osdd:cast']->getDefinition()->hasOption('inbound'));
        $this->assertTrue($commands['osdd:model']->getDefinition()->hasOption('migration'));
        $this->assertTrue($commands['osdd:controller']->getDefinition()->hasOption('requests'));
        $this->assertTrue($commands['osdd:migration']->getDefinition()->hasOption('create'));
    }

    /**
     * @return array<string, Command>
     */
    private function osddCommands(): array
    {
        $commands = array_filter(
            $this->app->make(Kernel::class)->all(),
            fn(string $name) => str_starts_with($name, 'osdd:'),
            ARRAY_FILTER_USE_KEY,
        );

        ksort($commands);

        return $commands;
    }
}
