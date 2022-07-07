<?php

declare(strict_types=1);

namespace Bamarni\Composer\Bin;

use Composer\Console\Application as ComposerApplication;
use Composer\Factory;
use Composer\IO\IOInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Composer\Command\BaseCommand;
use function chdir;
use function file_exists;
use function file_put_contents;
use function glob;
use function is_dir;
use function min;
use function mkdir;
use function putenv;
use function sprintf;

class BinCommand extends BaseCommand
{
    private const ALL_NAMESPACES = 'all';

    private const NAMESPACE_ARG = 'namespace';

    protected function configure(): void
    {
        $this
            ->setName('bin')
            ->setDescription('Run a command inside a bin namespace')
            ->setDefinition([
                new InputArgument(self::NAMESPACE_ARG, InputArgument::REQUIRED),
                new InputArgument('args', InputArgument::REQUIRED | InputArgument::IS_ARRAY),
            ])
            ->ignoreValidationErrors()
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $config = Config::fromComposer($this->requireComposer());
        $this->resetComposers($application = $this->getApplication());
        /** @var ComposerApplication $application */

        if ($config->binLinksAreEnabled()) {
            putenv('COMPOSER_BIN_DIR='.ConfigFactory::createConfig()->get('bin-dir'));
        }

        $vendorRoot = $config->getTargetDirectory();
        $namespace = $input->getArgument(self::NAMESPACE_ARG);

        $input = BinInputFactory::createInput(
            $namespace,
            $input
        );

        return (self::ALL_NAMESPACES !== $namespace)
            ? $this->executeInNamespace($application, $vendorRoot.'/'.$namespace, $input, $output)
            : $this->executeAllNamespaces($application, $vendorRoot, $input, $output)
        ;
    }

    private function executeAllNamespaces(
        ComposerApplication $application,
        string $binVendorRoot,
        InputInterface $input,
        OutputInterface $output
    ): int {
        $binRoots = glob($binVendorRoot.'/*', GLOB_ONLYDIR);
        if (empty($binRoots)) {
            $this->getIO()->writeError('<warning>Couldn\'t find any bin namespace.</warning>');

            return self::SUCCESS;   // Is a valid scenario: the user may not have setup any bin namespace yet
        }

        $originalWorkingDir = getcwd();
        $exitCode = self::SUCCESS;
        foreach ($binRoots as $namespace) {
            $output->writeln(
                sprintf('Run in namespace <comment>%s</comment>', $namespace),
                OutputInterface::VERBOSITY_VERBOSE
            );
            $exitCode += $this->executeInNamespace($application, $namespace, $input, $output);

            chdir($originalWorkingDir);
            $this->resetComposers($application);
        }

        return min($exitCode, self::FAILURE);
    }

    private function executeInNamespace(
        ComposerApplication $application,
        string $namespace,
        InputInterface $input,
        OutputInterface $output
    ): int {
        if (!file_exists($namespace)) {
            $mkdirResult = mkdir($namespace, 0777, true);

            if (!$mkdirResult && !is_dir($namespace)) {
                $this
                    ->getIO()
                    ->writeError(sprintf(
                        '<warning>Could not create the directory "%s".</warning>',
                        $namespace
                    ));

                return self::FAILURE;
            }
        }

        $this->chdir($namespace);

        // some plugins require access to composer file e.g. Symfony Flex
        if (!file_exists(Factory::getComposerFile())) {
            file_put_contents(Factory::getComposerFile(), '{}');
        }

        $namespaceInput = BinInputFactory::createNamespaceInput($input);

        $this->getIO()->writeError(
            sprintf('<info>Run with <comment>%s</comment></info>', $namespaceInput),
            true,
            IOInterface::VERBOSE
        );

        return $application->doRun($namespaceInput, $output);
    }

    public function isProxyCommand(): bool
    {
        return true;
    }

    private function resetComposers(ComposerApplication $application): void
    {
        $application->resetComposer();

        foreach ($this->getApplication()->all() as $command) {
            if ($command instanceof BaseCommand) {
                $command->resetComposer();
            }
        }
    }

    private function chdir(string $dir): void
    {
        chdir($dir);

        $this->getIO()->writeError(
            sprintf('<info>Changed current directory to %s</info>', $dir),
            true,
            IOInterface::VERBOSE
        );
    }
}
