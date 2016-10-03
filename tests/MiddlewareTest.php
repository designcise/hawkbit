<?php
/**
 * The Turbine Micro Framework. An advanced derivate of Proton Micro Framework
 *
 * @author Marco Bunge <marco_bunge@web.de>
 * @author Alex Bilbie <hello@alexbilbie.com>
 * @copyright Marco Bunge <marco_bunge@web.de>
 *
 * @license MIT
 */

namespace Turbine\Tests;


use Turbine\Application;
use Turbine\Application\MiddlewareRunner;
use Turbine\Application\ServiceProvidersFromConfigMiddleware;
use Turbine\Tests\TestAsset\TestServiceProvider;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequestFactory;

class MiddlewareTest extends \PHPUnit_Framework_TestCase
{

    public function testServiceProviderconfiguratorMiddleware()
    {
        $application = new Application([
            'providers' => [
                new TestServiceProvider()
            ]
        ]);
        $application->addMiddleware(new ServiceProvidersFromConfigMiddleware());

        //handle middlewares
        $application->handleMiddlewares($application, $application->getMiddlewares());

        $this->assertTrue($application->getContainer()->has('TestService'));

    }

    public function testMiddleWareRunner()
    {
        $middlewareRunner = new MiddlewareRunner([
            function ($request, $response, $next) {
                echo 1;
                if(true){

                }
                return $next($request, $response);
            },
            function ($request, $response, $next) {
                echo 2;
                return $next($request, $response);
            },
        ]);

        $middlewareRunner->run(ServerRequestFactory::fromGlobals(), new Response());
    }


}
