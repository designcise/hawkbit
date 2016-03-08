<?php
/**
 *
 * (c) Marco Bunge <marco_bunge@web.de>
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 *
 * Date: 07.03.2016
 * Time: 17:06
 *
 */

namespace TurbineTests\Stratigility;


use League\Route\Http\Exception\NotFoundException;
use Psr\Http\Message\ResponseInterface;
use Turbine\Application;
use Turbine\Stratigility\MiddlewarePipeAdapter;
use Zend\Diactoros\ServerRequestFactory;

class MiddlewareAdapterTest extends \PHPUnit_Framework_TestCase
{


    public function testFunctionalPiping()
    {
        $app = new Application();
        $app->get('/', function($request, ResponseInterface $response){
            $response->getBody()->write(' World');
        });
        $middleware = new MiddlewarePipeAdapter($app);

        $middleware->pipe('/', function($request, $response, $next){
            $response->getBody()->write('Hello');
        });

        $response = $middleware->__invoke(ServerRequestFactory::fromGlobals(), $app->getResponse());

        $this->assertEquals('Hello World', $response->getBody());
    }

    public function testNotFoundException()
    {
        $this->setExpectedException(NotFoundException::class);

        $middleware = new MiddlewarePipeAdapter(new Application());
        $middleware->setCatchErrors(false);

        $middleware->__invoke(ServerRequestFactory::fromGlobals(), $middleware->getApplication()->getResponse());
    }
}
