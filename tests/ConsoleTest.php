<?php
/**
 * Created by PhpStorm.
 * User: marco.bunge
 * Date: 01.02.2017
 * Time: 22:50
 */

namespace Hawkbit\Tests;


use Hawkbit\Console;
use League\CLImate\Argument\Manager;


class ConsoleTest extends \PHPUnit_Framework_TestCase
{

    public function testDispatching()
    {
        $handled = false;

        $console = new Console();
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

        $args = ['test', '--max', '10'];

        $console->handle($args);

        $this->assertTrue($handled);

    }
}
