<?php

namespace Bamarni\Composer\Bin;

use Composer\Plugin\Capability\CommandProvider as CommandProviderCapability;

class CommandProvider implements CommandProviderCapability
{
    public function getCommands(): array
    {
        return [new BinCommand];
    }
}
