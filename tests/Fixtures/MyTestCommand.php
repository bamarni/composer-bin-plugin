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
        // make sure the proxy command didn't instantiate Composer
        $this->assert->assertNull($this->getComposer(false));
        $this->assert->assertNull($this->getApplication()->getComposer(false));

        // put a dummy composer.json to be able to create Composer
        file_put_contents(getcwd().'/composer.json', '{}');

        $factory = Factory::create(new NullIO());
        $config = $factory->getConfig();

        $this->data[] = array(
            'bin-dir' => $config->get('bin-dir'),
            'cwd' => getcwd(),
            'vendor-dir' => $config->get('vendor-dir'),
        );

        $this->resetComposer();
        $this->getApplication()->resetComposer();
    }
}

