<?php

namespace Xefi\LaravelOSDD;

class SeederRegistry
{
    private array $seeders = [];

    /**
     * @param int $priority Lower values run first; ties preserve registration order
     * @param class-string<\Illuminate\Database\Seeder> ...$seeders
     */
    public function push(int $priority, string ...$seeders): void
    {
        foreach ($seeders as $seeder) {
            $this->seeders[] = ['priority' => $priority, 'class' => $seeder];
        }
    }

    /**
     * @return class-string<\Illuminate\Database\Seeder>[]
     */
    public function seeders(): array
    {
        $sorted = $this->seeders;
        usort($sorted, fn($a, $b) => $a['priority'] <=> $b['priority']);

        return array_column($sorted, 'class');
    }
}
