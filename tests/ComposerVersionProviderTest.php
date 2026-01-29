<?php

declare(strict_types=1);

namespace Bamarni\Composer\Bin\Tests;

use PHPUnit\Framework\TestCase;

use function preg_match;
use function sprintf;

/**
 * @group e2e
 * @covers \Bamarni\Composer\Bin\Tests\ComposerVersionProvider
 */
final class ComposerVersionProviderTest extends TestCase
{
    public function test_it_can_get_the_composer_version(): void
    {
        $expectedPattern = '/^\d+\.\d+\.\d+$/';

        $actual = ComposerVersionProvider::getComposerVersion();

        self::assertSame(
            1,
            preg_match($expectedPattern, $actual),
            sprintf(
                'The version "%s" does not match the pattern "%s".',
                $actual,
                $expectedPattern
            )
        );
    }
}
