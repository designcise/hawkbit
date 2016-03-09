<?php

namespace TurbineTests;

use League\Container\Container;
use League\Event\Emitter;
use League\Event\Event;
use League\Route\Http\Exception\NotFoundException;
use League\Route\RouteCollection;
use Monolog\Logger;
use Turbine;
use Turbine\Application;
use Turbine\ApplicationInterface;
use TurbineTests\TestAsset\SharedTestController;
use TurbineTests\TestAsset\TestController;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\EmitterInterface;
use Zend\Diactoros\Response\SapiStreamEmitter;
use Zend\Diactoros\Response\TextResponse;
use Zend\Diactoros\ServerRequestFactory;

class ApplicationTest extends \PHPUnit_Framework_TestCase
{

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

    public function testArrayAccessContainer()
    {
        $app = new Application();
        $app['foo'] = 'bar';

        $this->assertSame('bar', $app['foo']);
        $this->assertTrue(isset($app['foo']));
    }

    public function testSubscribe()
    {
        $app = new Application();

        $app->subscribe('request.received', function ($event, $request) {
            $this->assertInstanceOf('League\Event\Event', $event);
            $this->assertInstanceOf(ServerRequestInterface::class, $request);
        });

        $reflected = new \ReflectionProperty($app, 'emitter');
        $reflected->setAccessible(true);
        $emitter = $reflected->getValue($app);
        $this->assertTrue($emitter->hasListeners('request.received'));

        $app->get('/', function(){});

        $foo = null;
        $app->subscribe('response.created', function ($event, $request, $response) use (&$foo) {
            $foo = 'bar';
        });

        $request = ServerRequestFactory::fromGlobals();
        $response = $app->handle($request);

        $this->assertEquals('bar', $foo);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testTerminate()
    {
        $app = new Application();

        $app->subscribe('response.sent', function ($event, $request, $response) {
            $this->assertInstanceOf('League\Event\Event', $event);
            $this->assertInstanceOf(ServerRequestInterface::class, $request);
            $this->assertInstanceOf(ResponseInterface::class, $response);
        });

        $request = ServerRequestFactory::fromGlobals();
        $response = $app->handle($request);

        $app->terminate($request, $response);
    }

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

    public function testHandleWithOtherException()
    {
        $app = new Application();
        $app['debug'] = true;

        $request = ServerRequestFactory::fromGlobals();

        $app->subscribe($app::EVENT_REQUEST_RECEIVED, function ($event, $request, $response) {
            throw new \Exception('A test exception');
        });

        $response = $app->handle($request);

        $this->assertEquals(500, $response->getStatusCode());
    }

    public function testExceptionHandling()
    {
        $app = new Application();
        $app->setConfig('error', false);

        $request = ServerRequestFactory::fromGlobals();

        $app->subscribe($app::EVENT_RUNTIME_ERROR, function ($event, $exception) use ($app) {
            $this->assertInstanceOf(\Exception::class, $exception);
        });

        $app->subscribe($app::EVENT_LIFECYCLE_ERROR, function ($event, $exception, $request, ResponseInterface $errorResponse) use ($app) {
            $errorResponse->getBody()->write('Fail');
        });

        $response = $app->handle($request);

        $this->assertEquals(500, $response->getStatusCode());
        $toString = $response->getBody()->__toString();
        $this->assertEquals('Fail', $toString);
    }

    public function testNotFoundException()
    {
        $this->setExpectedException(NotFoundException::class);

        $app = new Application();
        $request = ServerRequestFactory::fromGlobals();
        $app->handle($request, null, false);
    }



    public function testCustomEvents()
    {
        $app = new Application();

        $time = null;
        $app->subscribe('custom.event', function ($event, $args) use (&$time) {
            $time = $args;
        });

        $app->getEventEmitter()->emit('custom.event', time());
        $this->assertTrue($time !== null);
    }

    public function testSetResponseEmitter()
    {
        $app = new Application();

        $app->get('/', function ($request, ResponseInterface $response) {
            $response->getBody()->write('<h1>It works!</h1>');
            return $response;
        });

        $app->getContainer()->add(EmitterInterface::class, new SapiStreamEmitter());

        $app->subscribe($app::EVENT_REQUEST_RECEIVED, function ($event, $request) {
            $this->assertInstanceOf(Event::class, $event);
            $this->assertInstanceOf(ServerRequestInterface::class, $request);
        });
        $app->subscribe($app::EVENT_RESPONSE_CREATED, function ($event, $request, $response) {
            $this->assertInstanceOf(Event::class, $event);
            $this->assertInstanceOf(ServerRequestInterface::class, $request);
            $this->assertInstanceOf(ResponseInterface::class, $response);
        });
        $app->subscribe($app::EVENT_RESPONSE_SENT, function ($event, $request, $response) {
            $this->assertInstanceOf(Event::class, $event);
            $this->assertInstanceOf(ServerRequestInterface::class, $request);
            $this->assertInstanceOf(ResponseInterface::class, $response);
        });
        $app->subscribe($app::EVENT_LIFECYCLE_COMPLETE, function ($event, $request, $response) {
            $this->assertInstanceOf(Event::class, $event);
            $this->assertInstanceOf(ServerRequestInterface::class, $request);
            $this->assertInstanceOf(ResponseInterface::class, $response);
        });

        $response = $app->handle(ServerRequestFactory::fromGlobals());
        $app->terminate($app->getRequest(), $response);
    }

    public function testRun()
    {
        $app = new Application();

        $app->get('/', function ($request, ResponseInterface $response) {
            $response->getBody()->write('<h1>It works!</h1>');
            return $response;
        });

        $app->subscribe('request.received', function ($event, $request) {
            $this->assertInstanceOf('League\Event\Event', $event);
            $this->assertInstanceOf(ServerRequestInterface::class, $request);
        });
        $app->subscribe('response.sent', function ($event, $request, $response) {
            $this->assertInstanceOf('League\Event\Event', $event);
            $this->assertInstanceOf(ServerRequestInterface::class, $request);
            $this->assertInstanceOf(ResponseInterface::class, $response);
        });

        ob_start();
        $app->run();
        ob_get_clean();
    }

    public function testEnvironment()
    {
        $app = new Application();
        $this->assertFalse($app->isHttp());
        $this->assertFalse($app->isAjax());
        $this->assertTrue($app->isCli());
    }

    public function testConfiguration(){
        $app = new Application(['foo' => 'bar']);

        $this->assertInternalType('array',$app->getConfig());
        $this->assertArrayHasKey('foo',$app->getConfig());
        $this->assertEquals('bar', $app->getConfig('foo'));
        $app->setConfig(['baz' => 'far']);
        $this->assertEquals('far', $app->getConfig('baz'));
        $app->setConfig('bar', 'foo');
        $this->assertEquals('foo', $app->getConfig('bar'));
    }
}
