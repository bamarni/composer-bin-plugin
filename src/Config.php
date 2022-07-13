<?php

declare(strict_types=1);

namespace Bamarni\Composer\Bin;

use Composer\Composer;
use function array_merge;

final class Config
{
    public const EXTRA_CONFIG_KEY = 'bamarni-bin';

    public const BIN_LINKS_ENABLED = 'bin-links';
    public const TARGET_DIRECTORY = 'target-directory';
    public const FORWARD_COMMAND = 'forward-command';

    private const DEFAULT_CONFIG = [
        self::BIN_LINKS_ENABLED => true,
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

        $this->binLinks = $config[self::BIN_LINKS_ENABLED];
        $this->targetDirectory = $config[self::TARGET_DIRECTORY];
        $this->forwardCommand = $config[self::FORWARD_COMMAND];
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
}
