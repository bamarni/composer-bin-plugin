<?php

namespace Bamarni\Composer\Bin;

use Composer\Composer;
use function array_merge;

final class Config
{
    /**
     * @var array{'bin-links': bool, 'target-directory': string, 'forward-command': bool}
     */
    private $config;

    public function __construct(Composer $composer)
    {
        $extra = $composer->getPackage()->getExtra();
        $this->config = array_merge(
            [
                'bin-links' => true,
                'target-directory' => 'vendor-bin',
                'forward-command' => false,
            ],
            $extra['bamarni-bin'] ?? []
        );
    }

    public function binLinksAreEnabled(): bool
    {
        return true === $this->config['bin-links'];
    }

    public function getTargetDirectory(): string
    {
        return $this->config['target-directory'];
    }

    public function isCommandForwarded(): bool
    {
        return $this->config['forward-command'];
    }
}
