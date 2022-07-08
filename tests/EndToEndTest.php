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
use function realpath;
use function sprintf;
use function str_replace;
use function trim;

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
            2
        );

        $expected = file_get_contents($scenarioPath.'/expected.txt');

        $scenarioProcess->run();

        self::assertTrue(
            $scenarioProcess->isSuccessful(),
            $scenarioProcess->getOutput().'|'.$scenarioProcess->getErrorOutput()
        );

        $actual = str_replace(
            getcwd(),
            '/path/to/project',
            file_get_contents($scenarioPath.'/actual.txt')
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
}
