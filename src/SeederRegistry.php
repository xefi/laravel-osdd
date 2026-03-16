<?php

namespace Xefi\LaravelOSDD;

class SeederRegistry
{
    private array $seeders = [];

    /**
     * @param class-string<\Illuminate\Database\Seeder> ...$seeders
     */
    public function push(string ...$seeders): void
    {
        array_push($this->seeders, ...$seeders);
    }

    /**
     * @return class-string<\Illuminate\Database\Seeder>[]
     */
    public function seeders(): array
    {
        return $this->seeders;
    }
}
