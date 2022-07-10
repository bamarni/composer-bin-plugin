<?php

declare(strict_types=1);

namespace Bamarni\Composer\Bin;

use Composer\Console\Application;

interface NamespaceApplicationFactory
{
    public function create(Application $existingApplication): Application;
}
