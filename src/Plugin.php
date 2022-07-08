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
use Symfony\Component\Console\Command\Command;
use Composer\Plugin\Capability\CommandProvider as ComposerPluginCommandProvider;
use Throwable;
use function in_array;
use function sprintf;

/**
 * @final Will be final in 2.x.
 */
class Plugin implements PluginInterface, Capable, EventSubscriberInterface
{
    private const FORWARDED_COMMANDS = ['install', 'update'];

    /**
     * @var Composer
     */
    protected $composer;

    /**
     * @var IOInterface
     */
    protected $io;

    /**
     * @var Logger
     */
    private $logger;

    public function activate(Composer $composer, IOInterface $io): void
    {
        $this->composer = $composer;
        $this->io = $io;
        $this->logger = new Logger($io);
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

    public static function getSubscribedEvents(): array
    {
        return [
            PluginEvents::COMMAND => 'onCommandEvent',
        ];
    }

    public function onCommandEvent(CommandEvent $event): bool
    {
        $this->logger->logDebug('Calling onCommandEvent().');

        $config = Config::fromComposer($this->composer);

        if ($config->isCommandForwarded()
            && in_array($event->getCommandName(), self::FORWARDED_COMMANDS, true)
        ) {
            return $this->onForwardedCommand($event);
        }

        return true;
    }

    protected function onForwardedCommand(CommandEvent $event): bool
    {
        $this->logger->logDebug('The command is being forwarded.');
        $this->logger->logDebug(
            sprintf(
                'Original input: <comment>%s</comment>.',
                $event->getInput()
            )
        );

        // Note that the input & output of $io should be the same as the event
        // input & output.
        $io = $this->io;

        $command = new BinCommand();
        $command->setComposer($this->composer);
        $command->setIO($io);
        $command->setApplication(new Application());

        $forwardedCommandInput = BinInputFactory::createForwardedCommandInput(
            $event->getInput()
        );

        try {
            return Command::SUCCESS === $command->run(
                $forwardedCommandInput,
                $event->getOutput()
            );
        } catch (Throwable $throwable) {
            return false;
        }
    }
}
