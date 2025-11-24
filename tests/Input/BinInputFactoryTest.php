<?php

declare(strict_types=1);

namespace Bamarni\Composer\Bin\Tests\Input;

use Bamarni\Composer\Bin\Input\BinInputFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\StringInput;

use function sprintf;

/**
 * @covers \Bamarni\Composer\Bin\Input\BinInputFactory
 */
final class BinInputFactoryTest extends TestCase
{
    /**
     * @dataProvider inputProvider
     */
    public function test_it_can_create_a_new_input(
        string $namespace,
        InputInterface $previousInput,
        InputInterface $expected
    ): void {
        $actual = BinInputFactory::createInput($namespace, $previousInput);

        self::assertEquals($expected, $actual);
    }

    public static function inputProvider(): iterable
    {
        $namespaceNames = [
            'simpleNamespaceName',
            'composed-namespaceName',
            'regexLimiter/namespaceName',
            'all',
        ];

        foreach ($namespaceNames as $namespaceName) {
            $labelPrefix = sprintf('[%s]', $namespaceName);

            yield $labelPrefix.'simple command' => [
                $namespaceName,
                new StringInput(
                    sprintf(
                        'bin %s show',
                        $namespaceName
                    )
                ),
                new StringInput('show'),
            ];

            yield $labelPrefix.' namespaced command' => [
                $namespaceName,
                new StringInput(
                    sprintf(
                        'bin %s check:platform',
                        $namespaceName
                    )
                ),
                new StringInput('check:platform'),
            ];

            yield $labelPrefix.'command with options' => [
                $namespaceName,
                new StringInput(
                    sprintf(
                        'bin %s show --tree -i',
                        $namespaceName
                    )
                ),
                new StringInput('show --tree -i'),
            ];

            yield $labelPrefix.'command with annoyingly placed options' => [
                $namespaceName,
                new StringInput(
                    sprintf(
                        '--ansi bin %s -o --quiet show --tree -i',
                        $namespaceName
                    )
                ),
                new StringInput('-o --quiet show --tree -i --ansi'),
            ];

            yield $labelPrefix.'command with options with option separator' => [
                $namespaceName,
                new StringInput(
                    sprintf(
                        'bin %s show --tree -i --',
                        $namespaceName
                    )
                ),
                new StringInput('show --tree -i --'),
            ];

            yield $labelPrefix.'command with options with option separator and follow up argument' => [
                $namespaceName,
                new StringInput(
                    sprintf(
                        'bin %s show --tree -i -- foo',
                        $namespaceName
                    )
                ),
                new StringInput('show --tree -i -- foo'),
            ];

            yield $labelPrefix.'command with options with option separator and follow up option' => [
                $namespaceName,
                new StringInput(
                    sprintf(
                        'bin %s show --tree -i -- --foo',
                        $namespaceName
                    )
                ),
                new StringInput('show --tree -i -- --foo'),
            ];

            yield $labelPrefix.'command with annoyingly placed options and option separator and follow up option' => [
                $namespaceName,
                new StringInput(
                    sprintf(
                        '--ansi bin %s -o --quiet show --tree -i -- --foo',
                        $namespaceName
                    )
                ),
                new StringInput('-o --quiet show --tree -i --ansi -- --foo'),
            ];
        }

        // See https://github.com/bamarni/composer-bin-plugin/pull/23
        yield [
            'foo-namespace',
            new StringInput('bin --ansi foo-namespace flex:update --prefer-lowest'),
            new StringInput('flex:update --prefer-lowest --ansi'),
        ];
    }

    /**
     * @dataProvider namespaceInputProvider
     */
    public function test_it_can_create_a_new_input_for_a_namespace(
        InputInterface $previousInput,
        InputInterface $expected
    ): void {
        $actual = BinInputFactory::createNamespaceInput($previousInput);

        self::assertEquals($expected, $actual);
    }

    public static function namespaceInputProvider(): iterable
    {
        $namespaceNames = [
            'simpleNamespaceName',
            'composed-namespaceName',
            'regexLimiter/namespaceName',
            'all',
        ];

        yield 'simple command' => [
            new StringInput('flex:update'),
            new StringInput('flex:update --working-dir=.'),
        ];

        yield 'command with options' => [
            new StringInput('flex:update --prefer-lowest -i'),
            new StringInput('flex:update --working-dir=. --prefer-lowest -i'),
        ];

        yield 'command with annoyingly placed options' => [
            new StringInput('-o --quiet flex:update --prefer-lowest -i'),
            new StringInput('-o --working-dir=. --quiet flex:update --prefer-lowest -i'),
        ];

        yield 'command with options with option separator' => [
            new StringInput('flex:update --prefer-lowest -i --'),
            new StringInput('flex:update --working-dir=. --prefer-lowest -i --'),
        ];

        yield 'command with options with option separator and follow up argument' => [
            new StringInput('flex:update --prefer-lowest -i -- foo'),
            new StringInput('flex:update --working-dir=. --prefer-lowest -i -- foo'),
        ];

        yield 'command with annoyingly placed options and option separator and follow up option' => [
            new StringInput('-o --quiet flex:update --prefer-lowest -i -- --foo'),
            new StringInput('-o --working-dir=. --quiet flex:update --prefer-lowest -i -- --foo'),
        ];
    }

    /**
     * @dataProvider forwardedCommandInputProvider
     */
    public function test_it_can_create_a_new_input_for_forwarded_command(
        InputInterface $previousInput,
        InputInterface $expected
    ): void {
        $actual = BinInputFactory::createForwardedCommandInput($previousInput);

        self::assertEquals($expected, $actual);
    }

    public static function forwardedCommandInputProvider(): iterable
    {
        yield [
            new StringInput('install --verbose'),
            new StringInput('bin all install --verbose'),
        ];

        yield [
            new StringInput('flex:update --prefer-lowest --ansi'),
            new StringInput('bin all flex:update --prefer-lowest --ansi'),
        ];
    }
}
