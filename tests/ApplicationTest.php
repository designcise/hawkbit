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

use League\Container\Container;
use League\Event\Emitter;
use League\Event\Event;
use League\Route\Http\Exception\NotFoundException;
use League\Route\RouteCollection;
use Monolog\Logger;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Turbine\Application;
use Turbine\Application\ApplicationEvent;
use Turbine\Tests\TestAsset\SharedTestController;
use Turbine\Tests\TestAsset\TestController;
use Zend\Diactoros\Response\EmitterInterface;
use Zend\Diactoros\Response\SapiStreamEmitter;
use Zend\Diactoros\ServerRequestFactory;

class ApplicationTest extends \PHPUnit_Framework_TestCase
{

    /**
     *
     */
    public function testConfiguration()
    {
        $app = new Application(['foo' => 'bar']);
        $this->assertInstanceOf(\ArrayAccess::class, $app->getConfig());
        $this->assertTrue($app->hasConfig('foo'));
        $this->assertEquals('bar', $app->getConfig('foo'));
        $app->setConfig(['baz' => 'far']);
        $this->assertEquals('far', $app->getConfig('baz'));
        $app->setConfig('bar', 'foo');
        $this->assertEquals('foo', $app->getConfig('bar'));
    }

    /**
     *
     */
    public function testConfigurationFromArrayObject()
    {
        $app = new Application(new \ArrayObject(['foo' => 'bar']));
        $this->assertInstanceOf(\ArrayAccess::class, $app->getConfig());
        $this->assertTrue($app->hasConfig('foo'));
        $this->assertEquals('bar', $app->getConfig('foo'));
        $app->setConfig(['baz' => 'far']);
        $this->assertEquals('far', $app->getConfig('baz'));
        $app->setConfig('bar', 'foo');
        $this->assertEquals('foo', $app->getConfig('bar'));
    }

    /**
     *
     */
    public function testSetGet()
    {
        $app = new Application();
        $this->assertTrue($app->getContainer() instanceof Container);
        $this->assertTrue($app->getRouter() instanceof RouteCollection);
        $this->assertTrue($app->getEventEmitter() instanceof Emitter);

        $logger = $app->getLogger();
        $this->assertTrue($logger instanceof Logger);
        $this->assertEquals($logger, $app->getLogger('default'));
    }

    /**
     *
     */
    public function testArrayAccessContainer()
    {
        $app = new Application();
        $app['foo'] = 'bar';

        $this->assertSame('bar', $app['foo']);
        $this->assertTrue(isset($app['foo']));
    }

    /**
     *
     */
    public function testaddListener()
    {
        $app = new Application(true);

        $app->addListener('request.received', function (ApplicationEvent $event) {
            $this->assertInstanceOf(ServerRequestInterface::class, $event->getRequest());
        });

        $this->assertTrue($app->getEmitter()->hasListeners('request.received'));

        $app->get('/', function () {
        });

        $foo = null;
        $app->addListener('response.created', function ($event) use (&$foo) {
            $foo = 'bar';
        });

        $request = ServerRequestFactory::fromGlobals();
        $response = $app->handle($request);

        $this->assertEquals('bar', $foo);
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     *
     */
    public function testTerminate()
    {
        $app = new Application();

        $app->addListener('response.sent', function (ApplicationEvent $event) {
            $this->assertInstanceOf('League\Event\Event', $event);
            $this->assertInstanceOf(ServerRequestInterface::class, $event->getRequest());
            $this->assertInstanceOf(ResponseInterface::class, $event->getResponse());
        });

        $request = ServerRequestFactory::fromGlobals();
        $response = $app->handle($request);

        $app->terminate($request, $response);
    }

    /**
     *
     */
    public function testHandleSuccess()
    {
        $app = new Application();

        $action = function ($request, ResponseInterface $response) {
            $response->getBody()->write('<h1>It works!</h1>');
            return $response;
        };
        $app->get('/', $action);

        $app->post('/', $action);

        $app->put('/', $action);

        $app->delete('/', $action);

        $app->patch('/', $action);

        $request = ServerRequestFactory::fromGlobals();

        $response = $app->handle($request, null, false);

        $content = $response->getBody();
        $this->assertEquals('<h1>It works!</h1>', $content);
    }

    /**
     *
     */
    public function testHandleControllerActionSuccess()
    {
        $app = new Application();

        $action = [TestController::class, 'getIndex'];

        $app->get('/', $action);
        $app->post('/', $action);
        $app->put('/', $action);
        $app->delete('/', $action);
        $app->patch('/', $action);

        $request = ServerRequestFactory::fromGlobals();

        $response = $app->handle($request, null, true);

        $content = $response->getBody()->__toString();
        $this->assertEquals('getIndex', $content);
    }

    /**
     *
     */
    public function testHandleAutoWiringControllerActionSuccess()
    {
        $app = new Application();
        $action = SharedTestController::class . '::getIndex';

        $app->get('/', $action);
        $app->post('/', $action);
        $app->put('/', $action);
        $app->delete('/', $action);
        $app->patch('/', $action);

        $request = ServerRequestFactory::fromGlobals();

        $response = $app->handle($request, null, false);

        $content = $response->getBody();
        $this->assertEquals($app->getConfig('customValueFromController'), $content);
    }

    /**
     *
     */
    public function testHandleWithOtherException()
    {
        $app = new Application();
        $app['debug'] = true;

        $request = ServerRequestFactory::fromGlobals();

        $app->addListener($app::EVENT_REQUEST_RECEIVED, function ($event) {
            throw new \Exception('A test exception');
        });

        $response = $app->handle($request);

        $this->assertEquals(500, $response->getStatusCode());
    }

    /**
     *
     */
    public function testExceptionHandling()
    {
        $app = new Application();
        $app->setConfig('error', false);

        $request = ServerRequestFactory::fromGlobals();

        $app->addListener($app::EVENT_RUNTIME_ERROR, function ($event, $exception) use ($app) {
            $this->assertInstanceOf(\Exception::class, $exception);
        });

        $app->addListener($app::EVENT_LIFECYCLE_ERROR, function (ApplicationEvent $event, $exception) use ($app) {
            $event->getErrorResponse()->getBody()->write('Fail');
        });

        $response = $app->handle($request);

        $this->assertEquals(500, $response->getStatusCode());
        $toString = $response->getBody()->__toString();
        $this->assertEquals('Fail', $toString);
    }

    /**
     *
     */
    public function testNotFoundException()
    {
        $this->setExpectedException(NotFoundException::class);

        $app = new Application();
        $request = ServerRequestFactory::fromGlobals();
        $app->handle($request, null, false);
    }


    /**
     *
     */
    public function testCustomEvents()
    {
        $app = new Application();

        $time = null;
        $app->addListener('custom.event', function ($event, $args) use (&$time) {
            $time = $args;
        });

        $app->getEventEmitter()->emit('custom.event', time());
        $this->assertTrue($time !== null);
    }

    /**
     *
     */
    public function testSetResponseEmitter()
    {
        $app = new Application();

        $app->get('/', function ($request, ResponseInterface $response) {
            $response->getBody()->write('<h1>It works!</h1>');
            return $response;
        });

        $app->getContainer()->add(EmitterInterface::class, new SapiStreamEmitter());

        $app->addListener($app::EVENT_REQUEST_RECEIVED, function (ApplicationEvent $event) {
            $this->assertInstanceOf(ApplicationEvent::class, $event);
            $this->assertInstanceOf(ServerRequestInterface::class, $event->getRequest());
        });

        $requestResponseCallback = function (ApplicationEvent $event) {
            $this->assertInstanceOf(ApplicationEvent::class, $event);
            $this->assertInstanceOf(ServerRequestInterface::class, $event->getRequest());
            $this->assertInstanceOf(ResponseInterface::class, $event->getResponse());
        };

        $app->addListener($app::EVENT_RESPONSE_CREATED, $requestResponseCallback);
        $app->addListener($app::EVENT_RESPONSE_SENT, $requestResponseCallback);
        $app->addListener($app::EVENT_LIFECYCLE_COMPLETE, $requestResponseCallback);

        ob_start();
        $app->run(ServerRequestFactory::fromGlobals());
        ob_end_clean();
    }

    /**
     *
     */
    public function testRun()
    {
        $app = new Application();

        $app->get('/', function ($request, ResponseInterface $response) {
            $response->getBody()->write('<h1>It works!</h1>');
            return $response;
        });

        $app->addListener('request.received', function (ApplicationEvent $event) {
            $this->assertInstanceOf(ApplicationEvent::class, $event);
            $this->assertInstanceOf(ServerRequestInterface::class, $event->getRequest());
        });
        $app->addListener('response.sent', function (ApplicationEvent $event) {
            $this->assertInstanceOf(ApplicationEvent::class, $event);
            $this->assertInstanceOf(ServerRequestInterface::class, $event->getRequest());
            $this->assertInstanceOf(ResponseInterface::class, $event->getResponse());
        });

        ob_start();
        $app->run();
        ob_get_clean();
    }

    /**
     *
     */
    public function testEnvironment()
    {
        $app = new Application();
        $this->assertFalse($app->isHttpRequest());
        $this->assertFalse($app->isAjaxRequest());
        $this->assertTrue($app->isCli());
    }

    /**
     *
     */
    public function testContainerHasNotClass()
    {
        $app = new Application();

        $this->assertFalse($app->getContainer()->has(\SplMaxHeap::class), 'Should not assert true, when class exists but is not part of container');
    }

    /**
     *
     */
    public function testContentTypeDelegation()
    {
        $app = new Application();
        $app->get('/', function ($request, ResponseInterface $response) {
            $response->getBody()->write('<h1>It works!</h1>');
            return $response;
        });

        $_SERVER['CONTENT_TYPE'] = 'application/json';
        $request = ServerRequestFactory::fromGlobals();

        $response = $app->handle($request);

        $this->assertEquals($request->getHeader('content-type'), $response->getHeader('content-type'));
        $this->assertEquals(['application/json'], $response->getHeader('content-type'));
    }

    public function testGetLoggers()
    {
        $app = new Application();
        $app->getLogger()->info('Hello there');
        $app->getLogger('another')->info('Hallo!');

        $this->assertTrue(in_array('default', $app->getLoggerChannels()));
        $this->assertTrue(in_array('another', $app->getLoggerChannels()));
        $this->assertFalse(in_array('nope', $app->getLoggerChannels()));

    }

}
