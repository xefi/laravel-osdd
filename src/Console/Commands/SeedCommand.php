<?php

namespace Xefi\LaravelOSDD\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Xefi\LaravelOSDD\SeederRegistry;

#[AsCommand(name: 'osdd:seed')]
class SeedCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'osdd:seed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run the seeder for all discovered OSDD layers';

    protected $signature = 'osdd:seed
        {--fresh : Run migrate:fresh before seeding}
        {--refresh : Run migrate:refresh before seeding}';

    public function handle(SeederRegistry $registry): int
    {
        if ($this->option('fresh')) {
            if (($exitCode = $this->call('migrate:fresh')) !== self::SUCCESS) {
                return $exitCode;
            }
        } elseif ($this->option('refresh')) {
            if (($exitCode = $this->call('migrate:refresh')) !== self::SUCCESS) {
                return $exitCode;
            }
        }

        $seeders = $registry->seeders();

        if (empty($seeders)) {
            $this->warn('No OSDD seeders registered. Make sure your layer ServiceProviders call loadSeeders().');

            return self::SUCCESS;
        }

        foreach ($seeders as $seederClass) {
            if (!class_exists($seederClass)) {
                $this->warn("Seeder class <comment>{$seederClass}</comment> not found, skipping.");
                continue;
            }

            $this->info("Seeding <comment>{$seederClass}</comment>...");
            $this->call('db:seed', ['--class' => $seederClass]);
        }

        return self::SUCCESS;
    }
}
