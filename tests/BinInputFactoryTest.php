<?php declare(strict_types=1);

namespace Bamarni\Composer\Bin\Tests;

use Bamarni\Composer\Bin\BinInputFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\StringInput;

final class BinInputFactoryTest extends TestCase
{
    /**
     * @dataProvider inputProvider
     */
    public function test_it_can_create_a_new_input(
        string $namespace,
        InputInterface $previousInput,
        InputInterface $expected
    ): void
    {
        $actual = BinInputFactory::createInput($namespace, $previousInput);

        self::assertEquals($expected, $actual);
    }

    public static function inputProvider(): iterable
    {
        yield [
            'foo-namespace',
            new StringInput('bin foo-namespace flex:update --prefer-lowest'),
            new StringInput('flex:update --prefer-lowest'),
        ];

        yield [
            'foo-namespace',
            new StringInput('bin foo-namespace flex:update --prefer-lowest --ansi'),
            new StringInput('flex:update --prefer-lowest --ansi'),
        ];

        // See https://github.com/bamarni/composer-bin-plugin/pull/23
        yield [
            'foo-namespace',
            new StringInput('bin --ansi foo-namespace flex:update --prefer-lowest'),
            new StringInput('--ansi flex:update --prefer-lowest'),
        ];
    }

    /**
     * @dataProvider namespaceInputProvider
     */
    public function test_it_can_create_a_new_input_for_a_namespace(
        InputInterface $previousInput,
        InputInterface $expected
    ): void
    {
        $actual = BinInputFactory::createNamespaceInput($previousInput);

        self::assertEquals($expected, $actual);
    }

    public static function namespaceInputProvider(): iterable
    {
        yield [
            new StringInput('flex:update --prefer-lowest'),
            new StringInput('flex:update --prefer-lowest --working-dir=.'),
        ];

        yield [
            new StringInput('flex:update --prefer-lowest --ansi'),
            new StringInput('flex:update --prefer-lowest --ansi --working-dir=.'),
        ];
    }
}
