<?php

namespace Bamarni\Composer\Bin;

use Composer\Factory;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\OutputInterface;
use Composer\Command\BaseCommand;

class BinCommand extends BaseCommand
{
    protected function configure()
    {
        $this
            ->setName('bin')
            ->setDefinition(array(
                new InputArgument('name', InputArgument::REQUIRED),
                new InputArgument('args', InputArgument::REQUIRED | InputArgument::IS_ARRAY),
            ))
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        putenv('COMPOSER_BIN_DIR='.Factory::createConfig()->get('bin-dir'));

        $binName = $input->getArgument('name');
        $binRoot = 'vendor-bin/'.$binName;

        if (!file_exists($binRoot)) {
            mkdir($binRoot, 0777, true);
        }
        chdir($binRoot);
        $this->getIO()->writeError('<info>Changed current directory to '.$binRoot.'</info>');

        $this->resetComposer();

        $input = new StringInput(preg_replace('/bin\s+'.preg_quote($binName, '/').'/', '', $input->__toString(), 1));

        return $this->getApplication()->run($input, $output);
    }
}
