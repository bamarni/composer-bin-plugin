<?php declare(strict_types=1);

namespace Bamarni\Composer\Bin;

use Composer\Config as ComposerConfig;
use Composer\Console\Application as ComposerApplication;
use Composer\Factory;
use Composer\IO\IOInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\OutputInterface;
use Composer\Command\BaseCommand;
use Composer\Json\JsonFile;
use function chdir;
use function file_exists;
use function file_put_contents;
use function glob;
use function min;
use function mkdir;
use function preg_quote;
use function preg_replace;
use function putenv;
use function sprintf;

class BinCommand extends BaseCommand
{
    protected function configure(): void
    {
        $this
            ->setName('bin')
            ->setDescription('Run a command inside a bin namespace')
            ->setDefinition([
                new InputArgument('namespace', InputArgument::REQUIRED),
                new InputArgument('args', InputArgument::REQUIRED | InputArgument::IS_ARRAY),
            ])
            ->ignoreValidationErrors()
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $config = new Config($this->getComposer());
        $this->resetComposers($application = $this->getApplication());
        /** @var ComposerApplication $application */

        if ($config->binLinksAreEnabled()) {
            putenv('COMPOSER_BIN_DIR='.$this->createConfig()->get('bin-dir'));
        }

        $vendorRoot = $config->getTargetDirectory();
        $namespace = $input->getArgument('namespace');

        $input = new StringInput(preg_replace(
            sprintf('/bin\s+(--ansi\s)?%s(\s.+)/', preg_quote($namespace, '/')),
            '$1$2',
            (string) $input,
            1
        ));

        return ('all' !== $namespace)
            ? $this->executeInNamespace($application, $vendorRoot.'/'.$namespace, $input, $output)
            : $this->executeAllNamespaces($application, $vendorRoot, $input, $output)
        ;
    }

    private function executeAllNamespaces(
        ComposerApplication $application,
        string $binVendorRoot,
        InputInterface $input,
        OutputInterface $output
    ): int
    {
        $binRoots = glob($binVendorRoot.'/*', GLOB_ONLYDIR);
        if (empty($binRoots)) {
            $this->getIO()->writeError('<warning>Couldn\'t find any bin namespace.</warning>');

            return 0;   // Is a valid scenario: the user may not have setup any bin namespace yet
        }

        $originalWorkingDir = getcwd();
        $exitCode = 0;
        foreach ($binRoots as $namespace) {
            $output->writeln(
                sprintf('Run in namespace <comment>%s</comment>', $namespace),
                OutputInterface::VERBOSITY_VERBOSE
            );
            $exitCode += $this->executeInNamespace($application, $namespace, $input, $output);

            chdir($originalWorkingDir);
            $this->resetComposers($application);
        }

        return min($exitCode, 255);
    }
    private function executeInNamespace(
        ComposerApplication $application,
        string $namespace,
        InputInterface $input,
        OutputInterface $output
    ): int
    {
        if (!file_exists($namespace)) {
            mkdir($namespace, 0777, true);
        }

        $this->chdir($namespace);

        // some plugins require access to composer file e.g. Symfony Flex
        if (!file_exists(Factory::getComposerFile())) {
            file_put_contents(Factory::getComposerFile(), '{}');
        }

        $input = new StringInput((string) $input . ' --working-dir=.');

        $this->getIO()->writeError(
            sprintf('<info>Run with <comment>%s</comment></info>', $input->__toString()),
            true,
            IOInterface::VERBOSE
        );

        return $application->doRun($input, $output);
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

    /**
     * @throws \Composer\Json\JsonValidationException
     * @throws \Seld\JsonLint\ParsingException
     *
     * @return ComposerConfig
     */
    private function createConfig(): ComposerConfig
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
