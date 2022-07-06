<?php

namespace Bamarni\Composer\Bin\Tests;

use Composer\Console\Application;
use Bamarni\Composer\Bin\BinCommand;
use Bamarni\Composer\Bin\Tests\Fixtures\MyTestCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\NullOutput;

class BinCommandTest extends TestCase
{
    private $application;
    private $myTestCommand;
    private $rootDir;

    protected function setUp(): void
    {
        $this->rootDir = sys_get_temp_dir().'/'.uniqid('composer_bin_plugin_tests_');
        mkdir($this->rootDir);
        chdir($this->rootDir);

        file_put_contents($this->rootDir.'/composer.json', '{}');

        $this->application = new Application();
        $this->application->addCommands([
            new BinCommand(),
            $this->myTestCommand = new MyTestCommand($this),
        ]);
    }

    public function tearDown(): void
    {
        putenv('COMPOSER_BIN_DIR');
        $this->myTestCommand->data = [];
    }

    /**
     * @dataProvider namespaceProvider
     */
    public function testNamespaceCommand($input)
    {
        $input = new StringInput($input);
        $output = new NullOutput();
        $this->application->doRun($input, $output);

        $this->assertCount(1, $this->myTestCommand->data);
        $dataSet = array_shift($this->myTestCommand->data);
        $this->assertEquals($dataSet['bin-dir'], $this->rootDir.'/vendor/bin');
        $this->assertEquals($dataSet['cwd'], $this->rootDir.'/vendor-bin/mynamespace');
        $this->assertEquals($dataSet['vendor-dir'], $this->rootDir.'/vendor-bin/mynamespace/vendor');
    }

    public static function namespaceProvider()
    {
        return [
            ['bin mynamespace mytest'],
            ['bin mynamespace mytest --myoption'],
        ];
    }

    public function testAllNamespaceWithoutAnyNamespace()
    {
        $input = new StringInput('bin all mytest');
        $output = new NullOutput();
        $this->application->doRun($input, $output);

        $this->assertEmpty($this->myTestCommand->data);
    }

    public function testAllNamespaceCommand()
    {
        $namespaces = ['mynamespace', 'yournamespace'];
        foreach ($namespaces as $ns) {
            mkdir($this->rootDir.'/vendor-bin/'.$ns, 0777, true);
        }

        $input = new StringInput('bin all mytest');
        $output = new NullOutput();
        $this->application->doRun($input, $output);

        $this->assertCount(count($namespaces), $this->myTestCommand->data);

        foreach ($namespaces as $ns) {
            $dataSet = array_shift($this->myTestCommand->data);
            $this->assertEquals($dataSet['bin-dir'], $this->rootDir . '/vendor/bin');
            $this->assertEquals($dataSet['cwd'], $this->rootDir . '/vendor-bin/'.$ns);
            $this->assertEquals($dataSet['vendor-dir'], $this->rootDir . '/vendor-bin/'.$ns.'/vendor');
        }
    }

    public function testBinDirFromLocalConfig()
    {
        $binDir = 'bin';
        $composer = [
            'config' => [
                'bin-dir' => $binDir
            ]
        ];
        file_put_contents($this->rootDir.'/composer.json', json_encode($composer));

        $input = new StringInput('bin theirspace mytest');
        $output = new NullOutput();
        $this->application->doRun($input, $output);

        $this->assertCount(1, $this->myTestCommand->data);
        $dataSet = array_shift($this->myTestCommand->data);
        $this->assertEquals($dataSet['bin-dir'], $this->rootDir.'/'.$binDir);
    }
}
