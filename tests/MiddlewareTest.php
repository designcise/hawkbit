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


use Psr\Http\Message\ResponseInterface;
use Turbine\Application;
use Turbine\Application\MiddlewareRunner;
use Turbine\Application\ServiceProvidersFromConfigMiddleware;
use Turbine\Tests\TestAsset\TestServiceProvider;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequestFactory;

class MiddlewareTest extends \PHPUnit_Framework_TestCase
{

    public function testMiddleWareRunner()
    {
        $middlewareRunner = new MiddlewareRunner([
            function ($request, ResponseInterface $response, $next) {
                $response->getBody()->write('before-');

                /** @var ResponseInterface $response */
                $response = $next($request, $response);
                $response->getBody()->write('after');
                return $response;
            },
            function ($request, ResponseInterface $response, $next) {
                $response->getBody()->write('last-');
                return $next($request, $response);
            },
        ]);

        /** @var ResponseInterface $response */
        $response = $middlewareRunner->run(ServerRequestFactory::fromGlobals(), new Response(), function ($request, ResponseInterface $response, $next) {
            $response->getBody()->write('final-');
            return $response;
        });

        $this->assertEquals('before-last-final-after',$response->getBody()->__toString());
    }


}
