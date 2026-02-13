<?php

declare(strict_types=1);

namespace Bamarni\Composer\Bin\Tests\Command;

use Bamarni\Composer\Bin\CommandForwardingContext;
use Bamarni\Composer\Bin\Command\BinCommand;
use Bamarni\Composer\Bin\Tests\Fixtures\MyTestCommand;
use Bamarni\Composer\Bin\Tests\Fixtures\ReuseApplicationFactory;
use Composer\Composer;
use Composer\Console\Application;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\NullOutput;

use function array_shift;
use function exec;
use function chdir;
use function file_exists;
use function file_put_contents;
use function getcwd;
use function json_encode;
use function mkdir;
use function putenv;
use function realpath;
use function sys_get_temp_dir;

/**
 * @covers \Bamarni\Composer\Bin\Command\BinCommand
 */
class BinCommandTest extends TestCase
{
    /**
     * @var Application
     */
    private $application;

    /**
     * @var MyTestCommand
     */
    private $testCommand;

    /**
     * @var non-empty-string
     */
    private $tmpDir;

    /**
     * @var string
     */
    private $previousCwd;

    protected function setUp(): void
    {
        $this->previousCwd = getcwd();

        $tmpDir = sys_get_temp_dir().'/composer_bin_plugin_tests';

        if (!file_exists($tmpDir)) {
            mkdir($tmpDir);
        }

        chdir($tmpDir);
        // On OSX sys_get_temp_dir() may return a symlink
        $tmpDirRealPath = realpath($tmpDir);
        self::assertNotFalse($tmpDirRealPath);
        $this->tmpDir = $tmpDirRealPath;

        file_put_contents(
            $this->tmpDir.'/composer.json',
            '{}'
        );

        $this->testCommand = new MyTestCommand();

        $this->application = new Application();
        $this->application->addCommands([
            new BinCommand(new ReuseApplicationFactory()),
            $this->testCommand,
        ]);
    }

    public function tearDown(): void
    {
        putenv('COMPOSER_BIN_DIR');

        chdir($this->previousCwd);
        exec('rm -rf ' . $this->tmpDir);

        unset($this->application);
        unset($this->testCommand);
        unset($this->previousCwd);
        unset($this->tmpDir);
    }

    /**
     * @dataProvider namespaceProvider
     */
    public function test_it_can_execute_the_bin_command(
        string $input,
        string $expectedRelativeBinDir,
        string $expectedRelativeCwd,
        string $expectedRelativeVendorDir
    ): void {
        $input = new StringInput($input);
        $output = new NullOutput();

        $this->application->doRun($input, $output);

        $this->assertHasAccessToComposer();
        $this->assertDataSetRecordedIs(
            $this->tmpDir.'/'.$expectedRelativeBinDir,
            $this->tmpDir.'/'.$expectedRelativeCwd,
            $this->tmpDir.'/'.$expectedRelativeVendorDir
        );
        $this->assertNoMoreDataFound();
    }

    public function test_the_all_namespace_can_be_called(): void
    {
        $input = new StringInput('bin all mytest');
        $output = new NullOutput();

        $this->application->doRun($input, $output);

        $this->assertNoMoreDataFound();
    }

    public function test_the_root_namespace_can_be_called(): void
    {
        self::assertFalse(CommandForwardingContext::isCommandForwardingDisabled());

        $input = new StringInput('bin root mytest');
        $output = new NullOutput();

        $this->application->doRun($input, $output);

        $this->assertHasAccessToComposer();
        $this->assertDataSetRecordedIs(
            $this->tmpDir.'/vendor/bin',
            $this->tmpDir,
            $this->tmpDir.'/vendor'
        );
        $this->assertNoMoreDataFound();
        self::assertFalse(CommandForwardingContext::isCommandForwardingDisabled());
    }

    public function test_a_command_can_be_executed_in_each_namespace_via_the_all_namespace(): void
    {
        $namespaces = ['namespace1', 'namespace2'];

        foreach ($namespaces as $namespace) {
            mkdir(
                $this->tmpDir.'/vendor-bin/'.$namespace,
                0777,
                true
            );
        }

        $input = new StringInput('bin all mytest');
        $output = new NullOutput();

        $this->application->doRun($input, $output);

        $this->assertHasAccessToComposer();

        foreach ($namespaces as $namespace) {
            $this->assertDataSetRecordedIs(
                $this->tmpDir . '/vendor/bin',
                $this->tmpDir . '/vendor-bin/'.$namespace,
                $this->tmpDir . '/vendor-bin/'.$namespace.'/vendor'
            );
        }

        $this->assertNoMoreDataFound();
    }

    public function test_the_bin_dir_can_be_changed(): void
    {
        $binDir = 'bin';
        $composer = [
            'config' => [
                'bin-dir' => $binDir
            ]
        ];

        file_put_contents(
            $this->tmpDir.'/composer.json',
            json_encode($composer)
        );

        $input = new StringInput('bin theirspace mytest');
        $output = new NullOutput();

        $this->application->doRun($input, $output);

        $this->assertHasAccessToComposer();
        $this->assertDataSetRecordedIs(
            $this->tmpDir.'/'.$binDir,
            $this->tmpDir.'/'.'vendor-bin/theirspace',
            $this->tmpDir.'/'.'vendor-bin/theirspace/vendor'
        );
        $this->assertNoMoreDataFound();
    }

    public static function namespaceProvider(): iterable
    {
        yield 'execute command from namespace' => [
            'bin testnamespace mytest',
            'vendor/bin',
            'vendor-bin/testnamespace',
            'vendor-bin/testnamespace/vendor',
        ];

        yield 'execute command with options from namespace' => [
            'bin testnamespace mytest --myoption',
            'vendor/bin',
            'vendor-bin/testnamespace',
            'vendor-bin/testnamespace/vendor',
        ];
    }

    private function assertHasAccessToComposer(): void
    {
        self::assertInstanceOf(
            Composer::class,
            $this->testCommand->composer,
            'Some plugins may require access to composer file e.g. Symfony Flex'
        );
    }

    private function assertDataSetRecordedIs(
        string $expectedBinDir,
        string $expectedCwd,
        string $expectedVendorDir
    ): void {
        $data = array_shift($this->testCommand->data);

        self::assertNotNull(
            $data,
            'Expected test command to contain at least one data entry'
        );
        self::assertSame($expectedBinDir, $data['bin-dir']);
        self::assertSame($expectedCwd, $data['cwd']);
        self::assertSame($expectedVendorDir, $data['vendor-dir']);
    }

    private function assertNoMoreDataFound(): void
    {
        $data = array_shift($this->testCommand->data);

        self::assertNull(
            $data,
            'Expected test command to contain not contain any more data entries.'
        );
    }
}
