<?php

declare(strict_types=1);

namespace Bamarni\Composer\Bin;

use Composer\Command\BaseCommand;
use Composer\Console\Application as ComposerApplication;
use Composer\Factory;
use Composer\IO\IOInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function chdir;
use function count;
use function file_exists;
use function file_put_contents;
use function getcwd;
use function glob;
use function min;
use function mkdir;
use function putenv;
use function sprintf;
use const GLOB_ONLYDIR;

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
            ->ignoreValidationErrors();
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $config = Config::fromComposer($this->requireComposer());
        $application = $this->getApplication();

        // Ensures Composer is reset – we are setting some environment variables
        // & co. so a fresh Composer instance is required.
        $this->resetComposers($application);

        if ($config->binLinksAreEnabled()) {
            putenv(sprintf(
                'COMPOSER_BIN_DIR=%s',
                ConfigFactory::createConfig()->get('bin-dir')
            ));
        }

        $vendorRoot = $config->getTargetDirectory();
        $namespace = $input->getArgument(self::NAMESPACE_ARG);

        $binInput = BinInputFactory::createInput(
            $namespace,
            $input
        );

        return (self::ALL_NAMESPACE !== $namespace)
            ? $this->executeInNamespace(
                $application,
                $vendorRoot.'/'.$namespace,
                $binInput,
                $output
            )
            : $this->executeAllNamespaces(
                $application,
                $vendorRoot,
                $binInput,
                $output
            );
    }

    /**
     * @return list<string>
     */
    private static function getBinNamespaces(string $binVendorRoot): array
    {
        return glob($binVendorRoot.'/*', GLOB_ONLYDIR);
    }

    private function executeAllNamespaces(
        ComposerApplication $application,
        string $binVendorRoot,
        InputInterface $input,
        OutputInterface $output
    ): int {
        $namespaces = self::getBinNamespaces($binVendorRoot);

        if (count($namespaces) === 0) {
            $this
                ->getIO()
                ->writeError('<warning>Could not find any bin namespace.</warning>');

            // Is a valid scenario: the user may not have set up any bin
            // namespace yet
            return self::SUCCESS;
        }

        $originalWorkingDir = getcwd();
        $exitCode = self::SUCCESS;

        foreach ($namespaces as $namespace) {
            $output->writeln(
                sprintf('Run in namespace <comment>%s</comment>', $namespace),
                OutputInterface::VERBOSITY_VERBOSE
            );

            $exitCode += $this->executeInNamespace($application, $namespace, $input, $output);

            // Ensure we have a clean state in-between each namespace execution
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

        // Some plugins require access to the Composer file e.g. Symfony Flex
        $namespaceComposerFile = Factory::getComposerFile();
        if (!file_exists($namespaceComposerFile)) {
            file_put_contents($namespaceComposerFile, '{}');
        }

        $namespaceInput = BinInputFactory::createNamespaceInput($input);

        $this->getIO()->writeError(
            sprintf(
                '<info>Run with <comment>%s</comment></info>',
                $namespaceInput
            ),
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
