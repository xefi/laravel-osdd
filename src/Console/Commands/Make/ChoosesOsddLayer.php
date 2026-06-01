<?php

namespace Xefi\LaravelOSDD\Console\Commands\Make;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Xefi\LaravelOSDD\Layers\Layer;
use Xefi\LaravelOSDD\Layers\LayersCollection;

use function Laravel\Prompts\search;

trait ChoosesOsddLayer
{
    protected ?Layer $resolvedLayer = null;

    protected function resolveLayer(): Layer
    {
        if ($this->resolvedLayer !== null) {
            return $this->resolvedLayer;
        }

        $layers = LayersCollection::fromConfig();

        if ($layerOption = $this->option('layer')) {
            $layer = $layers->first(fn(Layer $l) => $l->manifest->name() === $layerOption);

            if ($layer === null) {
                $this->components->error("Layer '{$layerOption}' not found.");
                throw new \RuntimeException("Layer '{$layerOption}' not found.");
            }

            return $this->resolvedLayer = $layer;
        }

        $chosen = search(
            label: 'Which layer should this be generated in?',
            options: fn(string $value) => $layers
                ->filter(fn(Layer $l) => str_contains($l->manifest->name(), $value))
                ->mapWithKeys(fn(Layer $l) => [$l->manifest->name() => $l->manifest->name()])
                ->sort()
                ->all(),
        );

        return $this->resolvedLayer = $layers->first(fn(Layer $l) => $l->manifest->name() === $chosen);
    }

    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        $this->resolveLayer();

        parent::interact($input, $output);
    }

    protected function getOptions(): array
    {
        return array_merge(parent::getOptions(), [
            ['layer', null, InputOption::VALUE_OPTIONAL, 'The layer to generate the file in'],
        ]);
    }
}
