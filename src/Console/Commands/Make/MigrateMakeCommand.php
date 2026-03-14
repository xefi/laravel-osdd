<?php

namespace Xefi\LaravelOSDD\Console\Commands\Make;

use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'osdd:migration')]
class MigrateMakeCommand extends \Illuminate\Database\Console\Migrations\MigrateMakeCommand
{
    use ChoosesOsddLayer;

    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'osdd:migration {name : The name of the migration}
        {--create= : The table to be created}
        {--table= : The table to migrate}
        {--fullpath : Output the full path of the migration (Deprecated)}
        {--layer= : The layer to generate the file in}';

    protected function getMigrationPath(): string
    {
        return $this->resolveLayer()->path . '/database/migrations';
    }
}
