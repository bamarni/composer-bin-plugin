<?php

declare(strict_types=1);

namespace Bamarni\Composer\Bin;

use Bamarni\Composer\Bin\CommandProvider as BamarniCommandProvider;
use Composer\Composer;
use Composer\Console\Application;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\CommandEvent;
use Composer\Plugin\PluginEvents;
use Composer\Plugin\PluginInterface;
use Composer\Plugin\Capable;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Composer\Plugin\Capability\CommandProvider as ComposerPluginCommandProvider;
use Throwable;
use function array_filter;
use function array_keys;

class Plugin implements PluginInterface, Capable, EventSubscriberInterface
{
    /**
     * @var Composer
     */
    protected $composer;

    /**
     * @var IOInterface
     */
    protected $io;

    public function activate(Composer $composer, IOInterface $io): void
    {
        $this->composer = $composer;
        $this->io = $io;
    }

    public function getCapabilities(): array
    {
        return [
            ComposerPluginCommandProvider::class => BamarniCommandProvider::class,
        ];
    }

    public function deactivate(Composer $composer, IOInterface $io): void
    {
    }

    public function uninstall(Composer $composer, IOInterface $io): void
    {
    }

    public static function getSubscribedEvents()
    {
        return [
            PluginEvents::COMMAND => 'onCommandEvent',
        ];
    }

    public function onCommandEvent(CommandEvent $event): bool
    {
        $config = new Config($this->composer);

        if ($config->isCommandForwarded()) {
            switch ($event->getCommandName()) {
                case 'update':
                case 'install':
                    return $this->onCommandEventInstallUpdate($event);
            }
        }

        return true;
    }

    protected function onCommandEventInstallUpdate(CommandEvent $event): bool
    {
        $command = new BinCommand();
        $command->setComposer($this->composer);
        $command->setApplication(new Application());

        $arguments = [
            'command' => $command->getName(),
            'namespace' => 'all',
            'args' => [],
        ];

        foreach (array_filter($event->getInput()->getArguments()) as $argument) {
            $arguments['args'][] = $argument;
        }

        foreach (array_keys(array_filter($event->getInput()->getOptions())) as $option) {
            $arguments['args'][] = '--' . $option;
        }

        $definition = new InputDefinition();
        $definition->addArgument(new InputArgument('command', InputArgument::REQUIRED));
        $definition->addArguments($command->getDefinition()->getArguments());
        $definition->addOptions($command->getDefinition()->getOptions());

        $input = new ArrayInput($arguments, $definition);

        try {
            $returnCode = $command->run($input, $event->getOutput());
        } catch (Throwable $throwable) {
            return false;
        }

        return $returnCode === 0;
    }
}
