<?php declare(strict_types=1);

namespace Bamarni\Composer\Bin;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\StringInput;
use function preg_quote;
use function preg_replace;
use function sprintf;

final class BinInputFactory
{
    public static function createInput(
        string $namespace,
        InputInterface $previousInput
    ): InputInterface
    {
        return new StringInput(
            preg_replace(
                sprintf('/bin\s+(--ansi\s)?%s(\s.+)/', preg_quote($namespace, '/')),
                '$1$2',
                (string) $previousInput,
                1
            )
        );
    }

    public static function createNamespaceInput(InputInterface $previousInput): InputInterface
    {
        return new StringInput((string) $previousInput . ' --working-dir=.');
    }

    private function __construct()
    {
    }
}
