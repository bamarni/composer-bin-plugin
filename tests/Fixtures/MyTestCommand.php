<?php

namespace Bamarni\Composer\Bin\Tests\Fixtures;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Composer\Command\BaseCommand;
use Composer\Factory;

class MyTestCommand extends BaseCommand
{
    public $data = array();

    private $assert;

    public function __construct(\PHPUnit_Framework_Assert $assert)
    {
        $this->assert = $assert;

        parent::__construct('mytest');
        $this->setDefinition(array(
            new InputOption('myoption', null, InputOption::VALUE_NONE),
        ));
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->assert->assertNull($this->getComposer(false));
        $this->assert->assertNull($this->getApplication()->getComposer(false));

        $config = Factory::createConfig();

        $this->data[] = array(
            'bin-dir' => $config->get('bin-dir'),
            'cwd' => getcwd(),
            'vendor-dir' => $config->get('vendor-dir'),
        );
    }
}

