<?php

namespace Xefi\LaravelOSDD\Tests\Console;

use Illuminate\Contracts\Console\Kernel;
use Symfony\Component\Console\Command\Command;
use Xefi\LaravelOSDD\Tests\TestCase;

class CommandRegistrationTest extends TestCase
{
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

    public function testEveryOsddCommandResolvesUnderTheNameItIsRegisteredWith(): void
    {
        $commands = $this->osddCommands();

        $this->assertNotEmpty($commands);

        foreach ($commands as $name => $command) {
            $this->assertSame(
                $name,
                $command->getName(),
                "Command '{$name}' resolves as '{$command->getName()}'.",
            );
        }
    }

    public function testEveryOsddGeneratorAcceptsALayerOption(): void
    {
        foreach ($this->osddCommands() as $name => $command) {
            if (in_array($name, self::NON_GENERATOR_COMMANDS, true)) {
                continue;
            }

            $this->assertTrue(
                $command->getDefinition()->hasOption('layer'),
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
