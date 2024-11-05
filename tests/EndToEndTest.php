<?php

declare(strict_types=1);

namespace Bamarni\Composer\Bin\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;

use function array_map;
use function basename;
use function dirname;
use function explode;
use function file_exists;
use function file_get_contents;
use function getcwd;
use function implode;
use function preg_replace;
use function realpath;
use function sprintf;
use function str_replace;
use function trim;

use const PHP_EOL;

/**
 * @group e2e
 * @coversNothing
 */
final class EndToEndTest extends TestCase
{
    private const E2E_DIR = __DIR__.'/../e2e';

    /**
     * @dataProvider scenarioProvider
     */
    public function test_it_passes_the_e2e_test(string $scenarioPath): void
    {
        $scenarioProcess = new Process(
            ['./script.sh'],
            $scenarioPath,
            null,
            null,
            10
        );

        $expected = self::normalizeTrailingWhitespacesAndLineReturns(
            file_get_contents($scenarioPath.'/expected.txt')
        );

        $scenarioProcess->run();

        $standardOutput = $scenarioProcess->getOutput();
        $errorOutput = $scenarioProcess->getErrorOutput();

        $actualPath = $scenarioPath.'/actual.txt';

        if (file_exists($actualPath)) {
            $originalContent = file_get_contents($scenarioPath.'/actual.txt');
            $originalContent = str_replace(
                "Symfony recipes are disabled: \"symfony/flex\" not found in the root composer.json\n\n",
                '',
                $originalContent
            );
            $originalContent = preg_replace(
                '/.+Symfony\\\\Flex.+\n/',
                '',
                $originalContent
            );
        } else {
            $originalContent = 'File was not created.';
        }

        $errorMessage = <<<TXT
Standard output:
{$standardOutput}
––––––––––––––––
Error output:
{$errorOutput}
––––––––––––––––
File content (actual.txt):
{$originalContent}
TXT;

        self::assertTrue(
            $scenarioProcess->isSuccessful(),
            $errorMessage
        );

        $actual = self::retrieveActualOutput(
            getcwd(),
            $originalContent
        );

        self::assertSame($expected, $actual, $errorMessage);
    }

    public static function scenarioProvider(): iterable
    {
        $scenarios = Finder::create()
            ->files()
            ->depth(1)
            ->in(self::E2E_DIR)
            ->name('README.md');

        foreach ($scenarios as $scenario) {
            $scenarioPath = dirname($scenario->getPathname());
            $scenarioName = basename($scenarioPath);
            $description = trim($scenario->getContents());

            $title = sprintf(
                '[%s] %s',
                $scenarioName,
                $description
            );

            yield $title => [
                realpath($scenarioPath),
            ];
        }
    }

    private static function retrieveActualOutput(
        string $cwd,
        string $originalContent
    ): string {
        $normalizedContent = str_replace(
            $cwd,
            '/path/to/project',
            $originalContent
        );

        // Sometimes in the CI a different log is shown, e.g. in https://github.com/bamarni/composer-bin-plugin/runs/7246889244
        $normalizedContent = preg_replace(
            '/> command: .+\n/',
            '',
            $normalizedContent
        );

        // Those values come from the expected.txt, it actually does matter how
        // many they are at instant t.
        $normalizedContent = preg_replace(
            '/Analyzed (\d+) packages to resolve dependencies/',
            'Analyzed 90 packages to resolve dependencies',
            $normalizedContent
        );
        $normalizedContent = preg_replace(
            '/Analyzed (\d+) rules to resolve dependencies/',
            'Analyzed 90 rules to resolve dependencies',
            $normalizedContent
        );

        // We are not interested in the exact version installed especially since
        // in a PR the version will be `dev-commithash` instead of `dev-master`.
        $normalizedContent = preg_replace(
            '/Installs: bamarni\/composer-bin-plugin:dev-.+/',
            'Installs: bamarni/composer-bin-plugin:dev-hash',
            $normalizedContent
        );
        $normalizedContent = preg_replace(
            '/Locking bamarni\/composer-bin-plugin \(dev-.+\)/',
            'Locking bamarni/composer-bin-plugin (dev-hash)',
            $normalizedContent
        );
        $normalizedContent = preg_replace(
            '/Installing bamarni\/composer-bin-plugin \(dev-.+\): Symlinking from \.\.\/\.\./',
            'Installing bamarni/composer-bin-plugin (dev-hash): Symlinking from ../..',
            $normalizedContent
        );

        // We are not interested in the time taken which can vary from locally
        // and on the CI.
        // Also since the place at which this line may change depending on where
        // it is run (i.e. is not deterministic), we simply remove it.
        $normalizedContent = preg_replace(
            '/Dependency resolution completed in \d\.\d{3} seconds\s/',
            '',
            $normalizedContent
        );

        // Normalize the find directory: on some versions of OSX it does not come
        // with ticks but it does on Linux (at least Ubuntu).
        $normalizedContent = preg_replace(
            '/find: ‘?(.+?)’?: No such file or directory/u',
            'find: $1: No such file or directory',
            $normalizedContent
        );

        return self::normalizeTrailingWhitespacesAndLineReturns($normalizedContent);
    }

    private static function normalizeTrailingWhitespacesAndLineReturns(string $value): string
    {
        return implode(
            "\n",
            array_map('rtrim', explode(PHP_EOL, $value))
        );
    }
}
