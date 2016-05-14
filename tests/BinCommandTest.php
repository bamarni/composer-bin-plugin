<?php

namespace Bamarni\Composer\Bin\Tests;

use Composer\Console\Application;
use Bamarni\Composer\Bin\BinCommand;
use Bamarni\Composer\Bin\Tests\Fixtures\MyTestCommand;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\ConsoleOutput;

class BinCommandTest extends \PHPUnit_Framework_TestCase
{
    private $application;
    private $myTestCommand;
    private $rootDir;

    protected function setUp()
    {
        $this->rootDir = sys_get_temp_dir().'/'.uniqid('composer_bin_plugin_tests_');
        mkdir($this->rootDir);
        chdir($this->rootDir);

        $this->application = new Application();
        $this->application->addCommands(array(
            new BinCommand(),
            $this->myTestCommand = new MyTestCommand($this),
        ));
    }

    public function tearDown()
    {
        putenv('COMPOSER_BIN_DIR');
        $this->myTestCommand->data = array();
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
        return array(
            array('bin mynamespace mytest'),
            array('bin mynamespace mytest --myoption'),
        );
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
        $namespaces = array('mynamespace', 'yournamespace');
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
}
