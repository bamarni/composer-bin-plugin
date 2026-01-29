<?php

declare(strict_types=1);

namespace Bamarni\Composer\Bin\Tests;

use Symfony\Component\Process\Process;

use function preg_match;

final class ComposerVersionProvider
{
    public static function getComposerVersion(): string
    {
        $composerVersionProcess = Process::fromShellCommandline(
            'composer --version --no-ansi --no-interaction',
            null,
            null,
            null,
            1
        );

        $composerVersionProcess->mustRun();

        $output = $composerVersionProcess->getOutput();

        return self::extractComposerVersion($output);
    }

    private static function extractComposerVersion(string $versionOutput): string
    {
        preg_match('/Composer version (?<version>\d+\.\d+\.\d+) /', $versionOutput, $matches);

        return $matches['version'];
    }
}
