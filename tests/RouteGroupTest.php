<?php
/**
 *
 * (c) Marco Bunge <marco_bunge@web.de>
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 *
 * Date: 24.03.2016
 * Time: 11:16
 *
 */

namespace TurbineTests;


use Turbine\Application;
use Zend\Diactoros\ServerRequestFactory;

class RouteGroupTest extends \PHPUnit_Framework_TestCase
{

    public function testGroupInstance()
    {
        $app = new Application();

        $callback = function ($route) {

            $isApp = $this;

            if (true) {

            }

        };

        $callback = \Closure::bind($callback, $app, get_class($app));
        
        $app->group('dev', $callback);
        $app->handle(ServerRequestFactory::fromGlobals());
    }
}
