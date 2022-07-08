<?php

declare(strict_types=1);

namespace Bamarni\Composer\Bin;

use Composer\Command\BaseCommand;
use Composer\Factory;
use Composer\IO\IOInterface;
use ReflectionClass;
use ReflectionProperty;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;
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

/**
 * @final Will be final in 2.x.
 */
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

    public function isProxyCommand(): bool
    {
        return true;
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $config = Config::fromComposer($this->requireComposer());
        $currentWorkingDir = getcwd();

        $this->log(
            sprintf(
                'Current working directory: <comment>%s</comment>',
                $currentWorkingDir
            )
        );

        // Ensures Composer is reset – we are setting some environment variables
        // & co. so a fresh Composer instance is required.
        $this->resetComposers();

        $this->configureBinLinksDir($config);

        $vendorRoot = $config->getTargetDirectory();
        $namespace = $input->getArgument(self::NAMESPACE_ARG);

        $binInput = BinInputFactory::createInput(
            $namespace,
            $input
        );

        $applicationReflection = new ReflectionClass(Application::class);
        $commandsReflection = $applicationReflection->getProperty('commands');
        $commandsReflection->setAccessible(true);

        return (self::ALL_NAMESPACES !== $namespace)
            ? $this->executeInNamespace(
                $currentWorkingDir,
                $vendorRoot.'/'.$namespace,
                $binInput,
                $output,
                $commandsReflection
            )
            : $this->executeAllNamespaces(
                $currentWorkingDir,
                $vendorRoot,
                $binInput,
                $output,
                $commandsReflection
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
        string $originalWorkingDir,
        string $binVendorRoot,
        InputInterface $input,
        OutputInterface $output,
        ReflectionProperty $commandsReflection
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

        $exitCode = self::SUCCESS;

        foreach ($namespaces as $namespace) {
            $exitCode += $this->executeInNamespace(
                $originalWorkingDir,
                $namespace,
                $input,
                $output,
                $commandsReflection
            );
        }

        return min($exitCode, self::FAILURE);
    }

    private function executeInNamespace(
        string $originalWorkingDir,
        string $namespace,
        InputInterface $input,
        OutputInterface $output,
        ReflectionProperty $commandsReflection
    ): int {
        $this->log(
            sprintf(
                'Checking namespace <comment>%s</comment>',
                $namespace
            )
        );

        try {
            self::createNamespaceDirIfDoesNotExist($namespace);
        } catch (CouldNotCreateNamespaceDir $exception) {
            $this->log(
                sprintf(
                    '<warning>%s</warning>',
                    $exception->getMessage()
                ),
                false
            );

            return self::FAILURE;
        }

        $application = $this->getApplication();
        $commands = $application->all();

        // It is important to clean up the state either for follow-up plugins
        // or for example the execution in the next namespace.
        $cleanUp = function () use (
            $originalWorkingDir,
            $commandsReflection,
            $application,
            $commands
        ): void {
            $this->chdir($originalWorkingDir);
            $this->resetComposers();

            // When executing composer in a namespace, some commands may be
            // registered.
            // For example when scripts are registered in the composer.json,
            // Composer adds them as commands to the application.
            $commandsReflection->setValue($application, $commands);
        };

        $this->chdir($namespace);

        $this->ensureComposerFileExists();

        $namespaceInput = BinInputFactory::createNamespaceInput($input);

        $this->log(
            sprintf(
                'Running <info>`@composer %s`</info>.',
                $namespaceInput
            )
        );

        try {
            $exitCode = $application->doRun($namespaceInput, $output);
        } catch (Throwable $executionFailed) {
            // Ensure we do the cleanup even in case of failure
            $cleanUp();

            throw $executionFailed;
        }

        $cleanUp();

        return $exitCode;
    }

    /**
     * @throws CouldNotCreateNamespaceDir
     */
    private static function createNamespaceDirIfDoesNotExist(string $namespace): void
    {
        if (file_exists($namespace)) {
            return;
        }

        $mkdirResult = mkdir($namespace, 0777, true);

        if (!$mkdirResult && !is_dir($namespace)) {
            throw CouldNotCreateNamespaceDir::forNamespace($namespace);
        }
    }

    private function configureBinLinksDir(Config $config): void
    {
        if (!$config->binLinksAreEnabled()) {
            return;
        }

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

    private function ensureComposerFileExists(): void
    {
        // Some plugins require access to the Composer file e.g. Symfony Flex
        $namespaceComposerFile = Factory::getComposerFile();

        if (file_exists($namespaceComposerFile)) {
            return;
        }

        file_put_contents($namespaceComposerFile, '{}');

        $this->log(
            sprintf(
                'Created the file <comment>%s</comment>.',
                $namespaceComposerFile
            )
        );
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
