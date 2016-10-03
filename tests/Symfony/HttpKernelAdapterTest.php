<?php
/**
 *
 * (c) Marco Bunge <marco_bunge@web.de>
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 *
 * Date: 04.03.2016
 * Time: 16:24
 *
 */

namespace Turbine\Tests\Symfony;


use League\Route\Http\Exception\NotFoundException;
use Turbine\Application;
use Turbine\Symfony\HttpKernelAdapter;
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
        $this->setExpectedException(NotFoundException::class);

        $adapter = new HttpKernelAdapter(new Application());
        $adapter->handle(Request::create('/'), 1, false);
    }

}
