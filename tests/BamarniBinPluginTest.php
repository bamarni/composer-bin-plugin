<?php

declare(strict_types=1);

namespace Bamarni\Composer\Bin\Tests;

use Bamarni\Composer\Bin\BamarniBinPlugin;
use Bamarni\Composer\Bin\CommandForwardingContext;
use Bamarni\Composer\Bin\Config\Config;
use Composer\Composer;
use Composer\IO\ConsoleIO;
use Composer\Package\PackageInterface;
use Composer\Plugin\CommandEvent;
use Composer\Plugin\PluginEvents;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @covers \Bamarni\Composer\Bin\BamarniBinPlugin
 */
final class BamarniBinPluginTest extends TestCase
{
    protected function tearDown(): void
    {
        CommandForwardingContext::setCommandForwardingDisabled(false);
    }

    public function test_it_does_not_forward_commands_when_the_context_disables_forwarding(): void
    {
        CommandForwardingContext::setCommandForwardingDisabled(true);

        $input = new ArgvInput(['composer', 'update']);

        $plugin = new BamarniBinPluginSpy();
        $plugin->activate(
            $this->createComposer(true),
            self::createConsoleIO($input)
        );

        $event = new CommandEvent(
            PluginEvents::COMMAND,
            'update',
            $input,
            new NullOutput()
        );

        self::assertTrue($plugin->onCommandEvent($event));
        self::assertSame(0, $plugin->forwardedCommandCount);
    }

    public function test_it_keeps_forwarding_commands_when_the_context_allows_forwarding(): void
    {
        $input = new ArgvInput(['composer', 'update']);

        $plugin = new BamarniBinPluginSpy();
        $plugin->activate(
            $this->createComposer(true),
            self::createConsoleIO($input)
        );

        $event = new CommandEvent(
            PluginEvents::COMMAND,
            'update',
            $input,
            new NullOutput()
        );

        self::assertTrue($plugin->onCommandEvent($event));
        self::assertSame(1, $plugin->forwardedCommandCount);
    }

    private static function createConsoleIO(InputInterface $input): ConsoleIO
    {
        return new ConsoleIO(
            $input,
            new NullOutput(),
            new HelperSet()
        );
    }

    private function createComposer(bool $forwardCommand): Composer
    {
        $package = $this->createMock(PackageInterface::class);
        $package
            ->method('getExtra')
            ->willReturn([
                Config::EXTRA_CONFIG_KEY => [
                    Config::BIN_LINKS_ENABLED => false,
                    Config::FORWARD_COMMAND => $forwardCommand,
                    Config::TARGET_DIRECTORY => 'vendor-bin',
                ],
            ]);

        $composer = $this->createMock(Composer::class);
        $composer->method('getPackage')->willReturn($package);

        return $composer;
    }
}

final class BamarniBinPluginSpy extends BamarniBinPlugin
{
    /**
     * @var int
     */
    public $forwardedCommandCount = 0;

    protected function onForwardedCommand(
        InputInterface $input,
        OutputInterface $output
    ): bool {
        ++$this->forwardedCommandCount;

        return true;
    }
}
