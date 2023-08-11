<?php

declare(strict_types=1);

namespace Bamarni\Composer\Bin\Config;

use Composer\Composer;
use function array_key_exists;
use function array_merge;
use function function_exists;
use function is_bool;
use function is_string;
use function sprintf;

final class Config
{
    public const EXTRA_CONFIG_KEY = 'bamarni-bin';

    public const BIN_LINKS_ENABLED = 'bin-links';
    public const TARGET_DIRECTORY = 'target-directory';
    public const FORWARD_COMMAND = 'forward-command';
    public const FORWARDED_COMMANDS = 'forwarded-commands';

    private const DEFAULT_CONFIG = [
        self::BIN_LINKS_ENABLED => true,
        self::TARGET_DIRECTORY => 'vendor-bin',
        self::FORWARD_COMMAND => false,
        self::FORWARDED_COMMANDS => ['install', 'update'],
    ];

    /**
     * @var bool
     */
    private $binLinks;

    /**
     * @var string
     */
    private $targetDirectory;

    /**
     * @var list<string>
     */
    private $forwardedCommands;

    /**
     * @var list<string>
     */
    private $deprecations = [];

    /**
     * @throws InvalidBamarniComposerExtraConfig
     */
    public static function fromComposer(Composer $composer): self
    {
        return new self($composer->getPackage()->getExtra());
    }

    /**
     * @param mixed[] $extra
     *
     * @throws InvalidBamarniComposerExtraConfig
     */
    public function __construct(array $extra)
    {
        $userExtra = $extra[self::EXTRA_CONFIG_KEY] ?? [];

        $config = array_merge(self::DEFAULT_CONFIG, $userExtra);

        $getType = function_exists('get_debug_type') ? 'get_debug_type' : 'gettype';

        $binLinks = $config[self::BIN_LINKS_ENABLED];

        if (!is_bool($binLinks)) {
            throw new InvalidBamarniComposerExtraConfig(
                sprintf(
                    'Expected setting "extra.%s.%s" to be a boolean value. Got "%s".',
                    self::EXTRA_CONFIG_KEY,
                    self::BIN_LINKS_ENABLED,
                    $getType($binLinks)
                )
            );
        }

        $binLinksSetExplicitly = array_key_exists(self::BIN_LINKS_ENABLED, $userExtra);

        if ($binLinks && !$binLinksSetExplicitly) {
            $this->deprecations[] = sprintf(
                'The setting "extra.%s.%s" will be set to "false" from 2.x onwards. If you wish to keep it to "true", you need to set it explicitly.',
                self::EXTRA_CONFIG_KEY,
                self::BIN_LINKS_ENABLED
            );
        }

        $targetDirectory = $config[self::TARGET_DIRECTORY];

        if (!is_string($targetDirectory)) {
            throw new InvalidBamarniComposerExtraConfig(
                sprintf(
                    'Expected setting "extra.%s.%s" to be a string. Got "%s".',
                    self::EXTRA_CONFIG_KEY,
                    self::TARGET_DIRECTORY,
                    $getType($targetDirectory)
                )
            );
        }

        $forwardCommand = $config[self::FORWARD_COMMAND];

        if (!is_bool($forwardCommand)) {
            throw new InvalidBamarniComposerExtraConfig(
                sprintf(
                    'Expected setting "extra.%s.%s" to be a boolean value. Got "%s".',
                    self::EXTRA_CONFIG_KEY,
                    self::FORWARD_COMMAND,
                    gettype($forwardCommand)
                )
            );
        }

        $forwardCommandSetExplicitly = array_key_exists(self::FORWARD_COMMAND, $userExtra);

        if (!$forwardCommand && !$forwardCommandSetExplicitly) {
            $this->deprecations[] = sprintf(
                'The setting "extra.%s.%s" will be set to "true" from 2.x onwards. If you wish to keep it to "false", you need to set it explicitly.',
                self::EXTRA_CONFIG_KEY,
                self::FORWARD_COMMAND
            );
        }

        $forwardedCommands = $config[self::FORWARDED_COMMANDS];

        if (!is_array($forwardedCommands)) {
            throw new InvalidBamarniComposerExtraConfig(
                sprintf(
                    'Expected setting "extra.%s.%s" to be an array value. Got "%s".',
                    self::EXTRA_CONFIG_KEY,
                    self::FORWARDED_COMMANDS,
                    gettype($forwardCommand)
                )
            );
        }

        $forwardedCommandsSetExplicitly = array_key_exists(self::FORWARDED_COMMANDS, $userExtra);

        $this->binLinks = $binLinks;
        $this->targetDirectory = $targetDirectory;
        $this->forwardedCommands = $forwardCommand ? $forwardedCommands : [];
    }

    public function binLinksAreEnabled(): bool
    {
        return $this->binLinks;
    }

    public function getTargetDirectory(): string
    {
        return $this->targetDirectory;
    }

    public function isCommandForwarded(?string $name = null): bool
    {
        // Trigger deprecation.
        if ($name === null) {
            return false;
        }

        return in_array($name, $this->forwardedCommands, true);
    }

    /**
     * @return list<string>
     */
    public function getDeprecations(): array
    {
        return $this->deprecations;
    }
}
