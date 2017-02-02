<?php
/**
 * Created by PhpStorm.
 * User: marco.bunge
 * Date: 01.02.2017
 * Time: 22:50
 */

namespace Hawkbit\Tests;


use Hawkbit\Console;
use Hawkbit\Tests\TestAsset\TestableCommand;
use League\CLImate\Argument\Manager;


class ConsoleTest extends \PHPUnit_Framework_TestCase
{

    private $argvMock = [__FILE__, 'test'];

    public function testDispatching()
    {
        $handled = false;

        $console = new Console();
        $test = $this;
        $console->map('test', function (Manager $args) use (&$handled){
            $handled = true;
            $this->assertEquals(10, $args->get('max'));
        }, [
            'max' => [
                'prefix'       => 'm',
                'longPrefix'   => 'max',
                'description'  => 'Max values',
                'required'    => true,
            ]
        ]);

        $args = array_merge($this->argvMock, ['--max', '10']);

        $console->handle($args);

        $this->assertTrue($handled);

    }

    public function testConfiguration()
    {
        $console = new Console(['dev' => 'val']);
        $this->assertEquals('val', $console->getConfig('dev'));
    }

    public function testConstructorInjection()
    {

        $console = new Console();
        $console->map('test', [TestableCommand::class, 'handle']);

        $args = $this->argvMock;

        $console->handle($args);
        $feedback = $console['testFeedback'];

        $this->assertInstanceOf(TestableCommand::class, $feedback);
    }


}
