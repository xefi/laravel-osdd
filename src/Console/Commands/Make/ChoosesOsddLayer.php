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

    /**
     * Rebuild the definition when the parent generator is declared with a $signature.
     *
     * Illuminate\Console\Command gives the inherited $signature precedence over the $name
     * declared here and skips specifyParameters(), so without this the command would name
     * itself after the parent make:* command and expose no --layer option.
     */
    protected function configureUsingFluentDefinition(): void
    {
        $osddName = $this->name;

        parent::configureUsingFluentDefinition();

        $this->restoreOsddName($osddName);
        $this->addLayerOption();
    }

    protected function getOptions(): array
    {
        return array_merge(parent::getOptions(), [$this->layerOption()]);
    }

    private function restoreOsddName(?string $osddName): void
    {
        if ($osddName === null) {
            return;
        }

        $this->setName($this->name = $osddName);
    }

    private function addLayerOption(): void
    {
        if ($this->getDefinition()->hasOption('layer')) {
            return;
        }

        $this->getDefinition()->addOption($this->layerOption());
    }

    private function layerOption(): InputOption
    {
        return new InputOption('layer', null, InputOption::VALUE_OPTIONAL, 'The layer to generate the file in');
    }
}
