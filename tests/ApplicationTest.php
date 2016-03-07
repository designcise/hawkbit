<?php

namespace TurbineTests;

use League\Container\Container;
use League\Event\Emitter;
use League\Route\Http\Exception\NotFoundException;
use League\Route\RouteCollection;
use Monolog\Logger;
use Turbine;
use Turbine\Application;
use TurbineTests\TestAsset\SharedTestController;
use TurbineTests\TestAsset\TestController;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
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

        $foo = null;
        $app->subscribe('response.created', function ($event, $request, $response) use (&$foo) {
            $foo = 'bar';
        });

        $request = ServerRequestFactory::fromGlobals();
        $response = $app->handle($request);

        $this->assertEquals('bar', $foo);
        $this->assertEquals(404, $response->getStatusCode());
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

        $response = $app->handle($request, 1, false);

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

        $response = $app->handle($request, 1, true);

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

        $response = $app->handle($request, 1, true);

        $content = $response->getBody();
        $this->assertEquals($app->getConfig('customValueFromController'), $content);
    }

    public function testHandleFailThrowException()
    {
        $app = new Application();

        $request = ServerRequestFactory::fromGlobals();

        try {
            $app->handle($request, 1, false);
        } catch (\Exception $e) {
            $this->assertTrue($e instanceof NotFoundException);
        }
    }

    public function testHandleWithOtherException()
    {
        $app = new Application();
        $app['debug'] = true;

        $request = ServerRequestFactory::fromGlobals();

        $app->subscribe('request.received', function ($event, $request, $response) {
            throw new \Exception('A test exception');
        });

        $response = $app->handle($request);

        $this->assertEquals(500, $response->getStatusCode());
    }

    public function testCustomExceptionDecorator()
    {
        $app = new Application();
        $app['debug'] = true;

        $request = ServerRequestFactory::fromGlobals();

        $app->subscribe('request.received', function ($event, $request, $response) {
            throw new \Exception('A test exception');
        });

        $app->setExceptionDecorator(function ($e) {
            return new TextResponse('Fail', 500);
        });

        $response = $app->handle($request);

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals('Fail', $response->getBody());
    }

    /**
     * @expectedException \LogicException
     */
    public function testExceptionDecoratorDoesntReturnResponseObject()
    {
        $app = new Application();
        $app->setExceptionDecorator(function ($e) {
            return true;
        });

        $request = ServerRequestFactory::fromGlobals();

        $app->subscribe('request.received', function ($event, $request, $response) {
            throw new \Exception('A test exception');
        });

        $app->handle($request);
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

        $app->setResponseEmitter(new SapiStreamEmitter());

        $app->subscribe('request.received', function ($event, $request) {
            $this->assertInstanceOf('League\Event\Event', $event);
            $this->assertInstanceOf(ServerRequestInterface::class, $request);
        });
        $app->subscribe('response.sent', function ($event, $request, $response) {
            $this->assertInstanceOf('League\Event\Event', $event);
            $this->assertInstanceOf(ServerRequestInterface::class, $request);
            $this->assertInstanceOf(ResponseInterface::class, $response);
        });

        $app->handle(ServerRequestFactory::fromGlobals());
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
}
