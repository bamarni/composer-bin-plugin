<?php

declare(strict_types=1);

namespace Bamarni\Composer\Bin\Tests;

use Bamarni\Composer\Bin\Config;
use PHPUnit\Framework\TestCase;
use UnexpectedValueException;

final class ConfigTest extends TestCase
{
    /**
     * @dataProvider provideExtraConfig
     */
    public function test_it_can_be_instantiated(
        array $extra,
        bool $expectedBinLinksEnabled,
        string $expectedTargetDirectory,
        bool $expectedForwardCommand
    ): void {
        $config = new Config($extra);

        self::assertSame($expectedBinLinksEnabled, $config->binLinksAreEnabled());
        self::assertSame($expectedTargetDirectory, $config->getTargetDirectory());
        self::assertSame($expectedForwardCommand, $config->isCommandForwarded());
    }

    public static function provideExtraConfig(): iterable
    {
        yield 'default values' => [
            [],
            true,
            'vendor-bin',
            false,
        ];

        yield 'unknown extra entry' => [
            ['unknown' => 'foo'],
            true,
            'vendor-bin',
            false,
        ];

        yield 'nominal' => [
            [
                Config::EXTRA_CONFIG_KEY => [
                    Config::BIN_LINKS_ENABLED => false,
                    Config::TARGET_DIRECTORY => 'tools',
                    Config::FORWARD_COMMAND => true,
                ],
            ],
            false,
            'tools',
            true,
        ];
    }

    /**
     * @dataProvider provideInvalidExtraConfig
     */
    public function test_it_cannot_be_instantiated_with_invalid_config(
        array $extra,
        string $expectedMessage
    ): void {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage($expectedMessage);

        new Config($extra);
    }

    public static function provideInvalidExtraConfig(): iterable
    {
        yield 'non bool bin links' => [
            [
                Config::EXTRA_CONFIG_KEY => [
                    Config::BIN_LINKS_ENABLED => 'foo',
                ],
            ],
            'Expected setting "bamarni-bin.bin-links" to be a boolean value. Got "string".',
        ];

        yield 'non string target directory' => [
            [
                Config::EXTRA_CONFIG_KEY => [
                    Config::TARGET_DIRECTORY => false,
                ],
            ],
            'Expected setting "bamarni-bin.target-directory" to be a string. Got "bool".',
        ];

        yield 'non bool forward command' => [
            [
                Config::EXTRA_CONFIG_KEY => [
                    Config::FORWARD_COMMAND => 'foo',
                ],
            ],
            'Expected setting "bamarni-bin.forward-command" to be a boolean value. Got "string".',
        ];
    }
}
