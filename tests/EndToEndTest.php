<?php

declare(strict_types=1);

namespace Bamarni\Composer\Bin\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;
use function basename;
use function dirname;
use function file_get_contents;
use function getcwd;
use function preg_replace;
use function realpath;
use function sprintf;
use function str_replace;
use function trim;

/**
 * @group e2e
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

        $expected = file_get_contents($scenarioPath.'/expected.txt');

        $scenarioProcess->run();

        $standardOutput = $scenarioProcess->getOutput();
        $errorOutput = $scenarioProcess->getErrorOutput();

        $originalContent = file_get_contents($scenarioPath.'/actual.txt');

        self::assertTrue(
            $scenarioProcess->isSuccessful(),
            <<<TXT
Standard output:
${standardOutput}
––––––––––––––––
Error output:
${errorOutput}
––––––––––––––––
File content (actual.txt):
${originalContent}
TXT
        );

        $actual = self::retrieveActualOutput(
            getcwd(),
            $originalContent
        );

        self::assertSame($expected, $actual);
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

        return $normalizedContent;
    }
}
