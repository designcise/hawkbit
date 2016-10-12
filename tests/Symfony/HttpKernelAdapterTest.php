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

namespace ZeroXF10\Turbine\Tests\Symfony;


use League\Route\Http\Exception\NotFoundException;
use ZeroXF10\Turbine\Application;
use ZeroXF10\Turbine\Symfony\HttpKernelAdapter;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\TerminableInterface;
use Zend\Diactoros\ServerRequestFactory;

class HttpKernelAdapterTest extends \PHPUnit_Framework_TestCase
{


    public function testImplementsInterfaces()
    {
        $app = new Application();

        $adapter = new HttpKernelAdapter($app);

        $this->assertTrue(is_subclass_of(HttpKernelAdapter::class, HttpKernelInterface::class));
        $this->assertTrue(is_subclass_of(HttpKernelAdapter::class, TerminableInterface::class));

        $request = Request::create('/');
        $response = $adapter->handle($request);

        $adapter->terminate($request, $response);
    }

    public function testHandleHttpKernel()
    {
        $app = new Application();

        $app->get('/', $action = function ($request, ResponseInterface $response) {
            $response->getBody()->write('FOOBAR');
            return $response;
        });

        $adapter = new HttpKernelAdapter($app);

        $response = $adapter->handle(Request::create('/'));
        $this->assertEquals('FOOBAR', $response->getContent());
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testTerminateHttpKernel()
    {
        $app = new Application();

        //is executed while terminate and should be similar
        $app->subscribe('response.created', function ($event, $request, $response) use(&$capturedRequest, &$capturedResponse) {
            $capturedRequest = $request;
            $capturedResponse = $response;
        });

        $app->get('/', $action = function ($request, ResponseInterface $response) {
            $response->getBody()->write('FOOBAR');
            return $response;
        });

        $adapter = new HttpKernelAdapter($app);

        $request = Request::create('/');
        $response = $adapter->handle($request);


        $this->assertEquals($adapter->getHttpFoundationFactory()->createRequest($capturedRequest)->getUri(), $request->getUri());
        $this->assertEquals($adapter->getHttpFoundationFactory()->createResponse($capturedResponse)->getContent(), $response->getContent());

        $adapter->terminate($request, $response);
    }

    public function testNotFoundException()
    {
//        $this->setExpectedException(NotFoundException::class);

        $adapter = new HttpKernelAdapter(new Application());
        $response = $adapter->handle(Request::create('/'), 1, false);

        $this->assertEquals(404, $response->getStatusCode());
    }

}
