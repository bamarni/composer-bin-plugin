<?php

declare(strict_types=1);

namespace Bamarni\Composer\Bin;

final class CommandForwardingContext
{
    /**
     * @var bool
     */
    private static $commandForwardingDisabled = false;

    public static function setCommandForwardingDisabled(bool $value): void
    {
        self::$commandForwardingDisabled = $value;
    }

    public static function isCommandForwardingDisabled(): bool
    {
        return self::$commandForwardingDisabled;
    }

    private function __construct()
    {
    }
}
