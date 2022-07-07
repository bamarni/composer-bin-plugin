<?php

declare(strict_types=1);

namespace Bamarni\Composer\Bin;

use Composer\Command\BaseCommand;
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
        $currentWorkingDir = getcwd();

        // Ensures Composer is reset – we are setting some environment variables
        // & co. so a fresh Composer instance is required.
        $this->resetComposers();

        if ($config->binLinksAreEnabled()) {
            $binDir = ConfigFactory::createConfig()->get('bin-dir');

            putenv(
                sprintf(
                    'COMPOSER_BIN_DIR=%s',
                    $binDir
                )
            );

            $this->log(
                sprintf(
                    'Configuring bin directory to <comment>%s</comment>.',
                    $binDir
                )
            );
        }

        $this->log(
            sprintf(
                'Current working directory: <comment>%s</comment>',
                $currentWorkingDir
            )
        );

        $vendorRoot = $config->getTargetDirectory();
        $namespace = $input->getArgument(self::NAMESPACE_ARG);

        $binInput = BinInputFactory::createInput(
            $namespace,
            $input
        );

        return (self::ALL_NAMESPACES !== $namespace)
            ? $this->executeInNamespace(
                $vendorRoot.'/'.$namespace,
                $binInput,
                $output
            )
            : $this->executeAllNamespaces(
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
        string $binVendorRoot,
        InputInterface $input,
        OutputInterface $output
    ): int {
        $namespaces = self::getBinNamespaces($binVendorRoot);

        if (count($namespaces) === 0) {
            $this->log(
                '<warning>Could not find any bin namespace.</warning>',
                false
            );

            // Is a valid scenario: the user may not have set up any bin
            // namespace yet
            return self::SUCCESS;
        }

        $originalWorkingDir = getcwd();
        $exitCode = self::SUCCESS;

        foreach ($namespaces as $namespace) {
            $exitCode += $this->executeInNamespace($namespace, $input, $output);

            // Ensure we have a clean state in-between each namespace execution
            $this->chdir($originalWorkingDir);
            $this->resetComposers();
        }

        return min($exitCode, self::FAILURE);
    }

    private function executeInNamespace(
        string $namespace,
        InputInterface $input,
        OutputInterface $output
    ): int {
        $this->log(
            sprintf(
                'Checking namespace <comment>%s</comment>',
                $namespace
            )
        );

        if (!file_exists($namespace)) {
            $mkdirResult = mkdir($namespace, 0777, true);

            if (!$mkdirResult && !is_dir($namespace)) {
                $this->log(
                    sprintf(
                        '<warning>Could not create the directory "%s".</warning>',
                        $namespace
                    ),
                    false
                );

                return self::FAILURE;
            }
        }

        $this->chdir($namespace);

        // Some plugins require access to the Composer file e.g. Symfony Flex
        $namespaceComposerFile = Factory::getComposerFile();
        if (!file_exists($namespaceComposerFile)) {
            file_put_contents($namespaceComposerFile, '{}');

            $this->log(
                sprintf(
                    'Created the file <comment>%s</comment>.',
                    $namespaceComposerFile
                )
            );
        }

        $namespaceInput = BinInputFactory::createNamespaceInput($input);

        $this->log(
            sprintf(
                'Running <info>`@composer %s`</info>.',
                $namespaceInput
            )
        );

        return $this->getApplication()->doRun($namespaceInput, $output);
    }

    public function isProxyCommand(): bool
    {
        return true;
    }

    private function resetComposers(): void
    {
        $this->getApplication()->resetComposer();

        foreach ($this->getApplication()->all() as $command) {
            if ($command instanceof BaseCommand) {
                $command->resetComposer();
            }
        }
    }

    private function chdir(string $dir): void
    {
        chdir($dir);

        $this->log(
            sprintf(
                'Changed current directory to <comment>%s</comment>.',
                $dir
            )
        );
    }

    private function log(string $message, bool $debug = true): void
    {
        $verbosity = $debug
            ? IOInterface::VERBOSE
            : IOInterface::NORMAL;

        $this->getIO()->writeError('[bamarni-bin-plugin] '.$message, true, $verbosity);
    }
}
