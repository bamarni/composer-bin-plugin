<?php

declare(strict_types=1);

namespace Bamarni\Composer\Bin;

use Composer\Composer;
use UnexpectedValueException;
use function array_merge;
use function function_exists;
use function gettype;
use function is_bool;
use function is_string;
use function sprintf;

final class Config
{
    private const EXTRA_CONFIG_KEY = 'bamarni-bin';

    private const BIN_LINKS = 'bin-links';
    private const TARGET_DIRECTORY = 'target-directory';
    private const FORWARD_COMMAND = 'forward-command';

    private const DEFAULT_CONFIG = [
        self::BIN_LINKS => true,
        self::TARGET_DIRECTORY => 'vendor-bin',
        self::FORWARD_COMMAND => false,
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
     * @var bool
     */
    private $forwardCommand;

    /**
     * @var list<string>
     */
    private $deprecations = [];

    public static function fromComposer(Composer $composer): self
    {
        return new self($composer->getPackage()->getExtra());
    }

    /**
     * @param mixed[] $extra
     */
    public function __construct(array $extra)
    {
        $config = array_merge(
            self::DEFAULT_CONFIG,
            $extra[self::EXTRA_CONFIG_KEY] ?? []
        );

        $getType = function_exists('get_debug_type') ? 'get_debug_type' : 'gettype';

        $binLinks = $config[self::BIN_LINKS];

        if (!is_bool($binLinks)) {
            throw new UnexpectedValueException(
                sprintf(
                    'Expected setting "%s.%s" to be a boolean value. Got "%s".',
                    self::EXTRA_CONFIG_KEY,
                    self::BIN_LINKS,
                    $getType($binLinks)
                )
            );
        }

        if ($binLinks) {
            $this->deprecations[] = sprintf(
                'The setting "%s.%s" will be set to "false" from 2.x onwards. If you wish to keep it to "true", you need to set it explicitly.',
                self::EXTRA_CONFIG_KEY,
                self::BIN_LINKS
            );
        }

        $targetDirectory = $config[self::TARGET_DIRECTORY];

        if (!is_string($targetDirectory)) {
            throw new UnexpectedValueException(
                sprintf(
                    'Expected setting "%s.%s" to be a string. Got "%s".',
                    self::EXTRA_CONFIG_KEY,
                    self::TARGET_DIRECTORY,
                    $getType($targetDirectory)
                )
            );
        }

        $forwardCommand = $config[self::FORWARD_COMMAND];

        if (!is_bool($forwardCommand)) {
            throw new UnexpectedValueException(
                sprintf(
                    'Expected setting "%s.%s" to be a boolean value. Got "%s".',
                    self::EXTRA_CONFIG_KEY,
                    self::FORWARD_COMMAND,
                    gettype($forwardCommand)
                )
            );
        }

        if (!$forwardCommand) {
            $this->deprecations[] = sprintf(
                'The setting "%s.%s" will be set to "true" from 2.x onwards. If you wish to keep it to "false", you need to set it explicitly.',
                self::EXTRA_CONFIG_KEY,
                self::BIN_LINKS
            );
        }

        $this->binLinks = $binLinks;
        $this->targetDirectory = $targetDirectory;
        $this->forwardCommand = $forwardCommand;
    }

    public function binLinksAreEnabled(): bool
    {
        return $this->binLinks;
    }

    public function getTargetDirectory(): string
    {
        return $this->targetDirectory;
    }

    public function isCommandForwarded(): bool
    {
        return $this->forwardCommand;
    }

    /**
     * @return list<string>
     */
    public function getDeprecations(): array
    {
        return $this->deprecations;
    }
}
