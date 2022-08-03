<?php

declare(strict_types=1);

namespace Bamarni\Composer\Bin\Tests\Config;

use Bamarni\Composer\Bin\Config\Config;
use Bamarni\Composer\Bin\Config\InvalidBamarniComposerExtraConfig;
use PHPUnit\Framework\TestCase;
use function function_exists;

/**
 * @covers \Bamarni\Composer\Bin\Config\Config
 */
final class ConfigTest extends TestCase
{
    /**
     * @dataProvider provideExtraConfig
     *
     * @param list<string> $expectedDeprecations
     */
    public function test_it_can_be_instantiated(
        array $extra,
        bool $expectedBinLinksEnabled,
        string $expectedTargetDirectory,
        bool $expectedForwardCommand,
        array $expectedDeprecations
    ): void {
        $config = new Config($extra);

        self::assertSame($expectedBinLinksEnabled, $config->binLinksAreEnabled());
        self::assertSame($expectedTargetDirectory, $config->getTargetDirectory());
        self::assertSame($expectedForwardCommand, $config->isCommandForwarded());
        self::assertSame($expectedDeprecations, $config->getDeprecations());
    }

    public static function provideExtraConfig(): iterable
    {
        $binLinksEnabledDeprecationMessage = 'The setting "bamarni-bin.bin-links" will be set to "false" from 2.x onwards. If you wish to keep it to "true", you need to set it explicitly.';
        $forwardCommandDeprecationMessage = 'The setting "bamarni-bin.forward-command" will be set to "true" from 2.x onwards. If you wish to keep it to "false", you need to set it explicitly.';

        yield 'default values' => [
            [],
            true,
            'vendor-bin',
            false,
            [
                $binLinksEnabledDeprecationMessage,
                $forwardCommandDeprecationMessage,
            ],
        ];

        yield 'unknown extra entry' => [
            ['unknown' => 'foo'],
            true,
            'vendor-bin',
            false,
            [
                $binLinksEnabledDeprecationMessage,
                $forwardCommandDeprecationMessage,
            ],
        ];

        yield 'same as default but explicit' => [
            [
                Config::EXTRA_CONFIG_KEY => [
                    Config::BIN_LINKS_ENABLED => true,
                    Config::FORWARD_COMMAND => false,
                ],
            ],
            true,
            'vendor-bin',
            false,
            [],
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
            [],
        ];
    }

    /**
     * @dataProvider provideInvalidExtraConfig
     */
    public function test_it_cannot_be_instantiated_with_invalid_config(
        array $extra,
        string $expectedMessage
    ): void {
        $this->expectException(InvalidBamarniComposerExtraConfig::class);
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
            function_exists('get_debug_type')
                ? 'Expected setting "bamarni-bin.target-directory" to be a string. Got "bool".'
                : 'Expected setting "bamarni-bin.target-directory" to be a string. Got "boolean".',
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
