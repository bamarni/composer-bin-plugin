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

        $binVendorRoot = 'vendor-bin';
        $binName = $input->getArgument('name');
        if ('all' === $binName) {
            $binRoots = glob($binVendorRoot.'/*', GLOB_ONLYDIR);
            $this->getApplication()->setAutoExit(false);
            $originalWorkingDir = getcwd();
        } else {
            $binRoots = array($binVendorRoot.'/'.$binName);
            if (!file_exists($binRoots[0])) {
                mkdir($binRoots[0], 0777, true);
            }
        }

        $exitCode = 0;
        foreach ($binRoots as $binRoot) {
            chdir($binRoot);

            $this->getIO()->writeError('<info>Changed current directory to ' . $binRoot . '</info>');

            $this->resetComposer();

            $input = new StringInput(preg_replace('/bin\s+' . preg_quote($binName, '/') . '/', '', $input->__toString(), 1));

            $exitCode += $this->getApplication()->run($input, $output);

            chdir($originalWorkingDir);
            foreach ($this->getApplication()->all() as $command) {
                if ($command instanceof BaseCommand) {
                    $command->resetComposer();
                }
            }
        }

        return $exitCode;
    }

    /**
     * {@inheritDoc}
     */
    public function isProxyCommand()
    {
        return true;
    }
}
