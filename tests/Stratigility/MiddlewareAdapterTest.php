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

namespace Turbine\Tests\Stratigility;


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
        $application = new Application();
        $application->get('/', function($request, ResponseInterface $response){
            $this->assertInstanceOf(ServerRequestInterface::class, $request);
            $this->assertInstanceOf(ResponseInterface::class, $response);
            $response->getBody()->write('Hello World');
        });
        $middleware = new MiddlewarePipeAdapter($application);

        $middleware->pipe('/', function($request, ResponseInterface $response, $next){
            $this->assertInstanceOf(ServerRequestInterface::class, $request);
            $this->assertInstanceOf(ResponseInterface::class, $response);
            $this->assertTrue(is_callable($next));

            $response->getBody()->write('<h1>');

            $response = $next($request, $response);

            $response->getBody()->write('</h1>');
        });

        $response = $middleware(ServerRequestFactory::fromGlobals(), $application->getResponse());

        $this->assertEquals('<h1>Hello World</h1>', $response->getBody());

    }

    public function testNotFoundException()
    {
        $this->setExpectedException(NotFoundException::class);

        $application = new Application();
        $application->setConfig($application::KEY_ERROR_CATCH, false);
        $middleware = new MiddlewarePipeAdapter($application);

        $middleware->__invoke(ServerRequestFactory::fromGlobals(), $middleware->getApplication()->getResponse());
    }
}
