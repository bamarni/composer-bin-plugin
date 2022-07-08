<?php

declare(strict_types=1);

namespace Bamarni\Composer\Bin\Tests;

use Bamarni\Composer\Bin\Logger;
use Composer\IO\BufferIO;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\OutputInterface;
use function array_diff;
use const PHP_EOL;

/**
 * @covers \Bamarni\Composer\Bin\Logger
 */
final class LoggerTest extends TestCase
{
    private const VERBOSITIES = [
        OutputInterface::VERBOSITY_QUIET,
        OutputInterface::VERBOSITY_NORMAL,
        OutputInterface::VERBOSITY_VERBOSE,
        OutputInterface::VERBOSITY_VERY_VERBOSE,
        OutputInterface::VERBOSITY_DEBUG,
    ];

    /**
     * @dataProvider standardMessageProvider
     */
    public function test_it_can_log_standard_messages(
        int $verbosity,
        string $message,
        string $expected
    ): void {
        $io = new BufferIO('', $verbosity);
        $logger = new Logger($io);

        $logger->logStandard($message);

        self::assertSame($expected, $io->getOutput());
    }

    public static function standardMessageProvider(): iterable
    {
        $notLoggedVerbosities = [
            OutputInterface::VERBOSITY_QUIET,
        ];

        $loggedVerbosities = array_diff(
            self::VERBOSITIES,
            $notLoggedVerbosities
        );

        $message = 'Hello world!';
        $expected = '[bamarni-bin-plugin] Hello world!'.PHP_EOL;

        foreach ($notLoggedVerbosities as $verbosity) {
            yield [$verbosity, $message, ''];
        }

        foreach ($loggedVerbosities as $verbosity) {
            yield [$verbosity, $message, $expected];
        }
    }

    /**
     * @dataProvider standardMessageProvider
     */
    public function test_it_can_log_debug_messages(
        int $verbosity,
        string $message,
        string $expected
    ): void {
        $io = new BufferIO('', $verbosity);
        $logger = new Logger($io);

        $logger->logStandard($message);

        self::assertSame($expected, $io->getOutput());
    }

    public static function debugMessageProvider(): iterable
    {
        $notLoggedVerbosities = [
            OutputInterface::VERBOSITY_QUIET,
            OutputInterface::VERBOSITY_NORMAL,
        ];

        $loggedVerbosities = array_diff(
            self::VERBOSITIES,
            $notLoggedVerbosities
        );

        $message = 'Hello world!';
        $expected = '[bamarni-bin-plugin] Hello world!'.PHP_EOL;

        foreach ($notLoggedVerbosities as $verbosity) {
            yield [$verbosity, $message, ''];
        }

        foreach ($loggedVerbosities as $verbosity) {
            yield [$verbosity, $message, $expected];
        }
    }
}
