<?php

declare(strict_types=1);

namespace Bamarni\Composer\Bin\Tests\Fixtures;

use Bamarni\Composer\Bin\NamespaceApplicationFactory;
use Composer\Console\Application;

final class ReuseApplicationFactory implements NamespaceApplicationFactory
{
    public function create(Application $existingApplication): Application
    {
        return $existingApplication;
    }
}
