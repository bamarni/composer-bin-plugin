<?php

namespace Bamarni\Composer\Bin;

use Composer\Factory;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\OutputInterface;
use Composer\Command\BaseCommand;
use Composer\Json\JsonFile;

class BinCommand extends BaseCommand
{
    protected function configure()
    {
        $this
            ->setName('bin')
            ->setDescription('Run a command inside a bin namespace')
            ->setDefinition(array(
                new InputArgument('namespace', InputArgument::REQUIRED),
                new InputArgument('args', InputArgument::REQUIRED | InputArgument::IS_ARRAY),
            ))
            ->ignoreValidationErrors()
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->resetComposers();

        putenv('COMPOSER_BIN_DIR='.$this->createConfig()->get('bin-dir'));

        $binVendorRoot = 'vendor-bin';
        $binNamespace = $input->getArgument('namespace');
        $input = new StringInput(preg_replace('/bin\s+' . preg_quote($binNamespace, '/') . '/', '', $input->__toString(), 1));

        if ('all' !== $binNamespace) {
            $binRoot = $binVendorRoot.'/'.$binNamespace;
            if (!file_exists($binRoot)) {
                mkdir($binRoot, 0777, true);
            }

            $this->chdir($binRoot);

            return $this->getApplication()->doRun($input, $output);
        }

        $binRoots = glob($binVendorRoot.'/*', GLOB_ONLYDIR);
        if (empty($binRoots)) {
            $this->getIO()->writeError('<warning>Couldn\'t find any bin namespace.</warning>');

            return;
        }

        $originalWorkingDir = getcwd();
        $exitCode = 0;
        foreach ($binRoots as $binRoot) {
            $this->chdir($binRoot);

            $exitCode += $this->getApplication()->doRun($input, $output);

            chdir($originalWorkingDir);
            $this->resetComposers();
        }

        return min($exitCode, 255);
    }

    /**
     * {@inheritDoc}
     */
    public function isProxyCommand()
    {
        return true;
    }

    private function resetComposers()
    {
        $this->getApplication()->resetComposer();
        foreach ($this->getApplication()->all() as $command) {
            if ($command instanceof BaseCommand) {
                $command->resetComposer();
            }
        }
    }

    private function chdir($dir)
    {
        chdir($dir);
        $this->getIO()->writeError('<info>Changed current directory to ' . $dir . '</info>');
    }

    private function createConfig()
    {
        $config = Factory::createConfig();

        $file = new JsonFile(Factory::getComposerFile());
        if (!$file->exists()) {
            return $config;
        }
        $file->validateSchema(JsonFile::LAX_SCHEMA);

        $config->merge($file->read());

        return $config;
    }
}