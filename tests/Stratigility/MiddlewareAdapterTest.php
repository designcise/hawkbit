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
use Psr\Http\Message\ServerRequestInterface;
use Turbine\Application;
use Turbine\Stratigility\MiddlewarePipeAdapter;
use Zend\Diactoros\ServerRequestFactory;

class MiddlewareAdapterTest extends \PHPUnit_Framework_TestCase
{


    public function testFunctionalPiping()
    {
        $app = new Application();
        $app->get('/', function($request, ResponseInterface $response){
            $this->assertInstanceOf(ServerRequestInterface::class, $request);
            $this->assertInstanceOf(ResponseInterface::class, $response);
            $response->getBody()->write('World');
        });
        $middleware = new MiddlewarePipeAdapter($app);

        $middleware->pipe('/', function($request, ResponseInterface $response, $next){
            $this->assertInstanceOf(ServerRequestInterface::class, $request);
            $this->assertInstanceOf(ResponseInterface::class, $response);
            $this->assertTrue(is_callable($next));

            $response->getBody()->write('Hello ');
        });

        $response = $middleware->__invoke(ServerRequestFactory::fromGlobals(), $app->getResponse());

        $content = $response->getBody()->__toString();
        $this->assertEquals('Hello World', $content);

    }

    public function testNotFoundException()
    {
        $this->setExpectedException(NotFoundException::class);

        $middleware = new MiddlewarePipeAdapter(new Application());
        $middleware->setCatchErrors(false);

        $middleware->__invoke(ServerRequestFactory::fromGlobals(), $middleware->getApplication()->getResponse());
    }
}
