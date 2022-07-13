<?php

declare(strict_types=1);

namespace Bamarni\Composer\Bin\Tests;

use Bamarni\Composer\Bin\Config;
use PHPUnit\Framework\TestCase;

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
}
