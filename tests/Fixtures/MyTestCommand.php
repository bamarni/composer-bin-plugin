<?php

namespace Bamarni\Composer\Bin\Tests\Fixtures;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Composer\Command\BaseCommand;
use Composer\Factory;
use Composer\IO\NullIO;

class MyTestCommand extends BaseCommand
{
    public $data = [];

    private $assert;

    public function __construct(\PHPUnit_Framework_Assert $assert)
    {
        $this->assert = $assert;

        parent::__construct('mytest');
        $this->setDefinition([
            new InputOption('myoption', null, InputOption::VALUE_NONE),
        ]);
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->assert->assertInstanceOf(
            '\Composer\Composer',
            $this->getComposer(),
            "Some plugins may require access to composer file e.g. Symfony Flex"
        );

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

