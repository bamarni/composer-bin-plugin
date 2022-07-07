<?php

declare(strict_types=1);

namespace Bamarni\Composer\Bin\Tests\Fixtures;

use Composer\Composer;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Composer\Command\BaseCommand;
use Composer\Factory;
use Composer\IO\NullIO;

class MyTestCommand extends BaseCommand
{
    /**
     * @var mixed|Composer
     */
    public $composer;

    /**
     * @var list<array{'bin-dir': string, 'cwd': string, 'vendor-bin': string}>
     */
    public $data = [];

    public function __construct()
    {
        parent::__construct('mytest');

        $this->setDefinition([
            new InputOption(
                'myoption',
                null,
                InputOption::VALUE_NONE
            ),
        ]);
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->composer = $this->getComposer();

        $factory = Factory::create(new NullIO());
        $config = $factory->getConfig();

        $this->data[] = [
            'bin-dir' => $config->get('bin-dir'),
            'cwd' => getcwd(),
            'vendor-dir' => $config->get('vendor-dir'),
        ];

        $this->resetComposer();
        $this->getApplication()->resetComposer();
    }
}
