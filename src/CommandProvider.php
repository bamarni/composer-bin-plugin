<?php

declare(strict_types=1);

namespace Bamarni\Composer\Bin;

use Composer\Plugin\Capability\CommandProvider as CommandProviderCapability;

/**
 * @final Will be made final in 2.0.
 */
class CommandProvider implements CommandProviderCapability
{
    public function getCommands(): array
    {
        return [new BinCommand()];
    }
}
