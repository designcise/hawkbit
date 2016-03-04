<?php
/**
 * The Proton Micro Framework.
 *
 * @author  Alex Bilbie <hello@alexbilbie.com>
 * @author  Marco Bunge <marco_bunge@web.de>
 *
 * @license MIT
 */

namespace Proton;

use League\Container\ContainerAwareInterface;
use League\Container\ContainerInterface;
use League\Event\EmitterTrait;
use League\Event\ListenerAcceptorInterface;
use Proton\Psr7\HttpKernelInterface;
use Proton\Psr7\TerminableInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use League\Container\Container;
use League\Route\RouteCollection;
use Monolog\Logger;
use Zend\Diactoros\Response;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\ServerRequestFactory;

/**
 * Proton Application Class.
 */
class Application implements ContainerAwareInterface, HttpKernelInterface, ListenerAcceptorInterface, TerminableInterface, \ArrayAccess
{
    use EmitterTrait;

    /**
     * @var \League\Route\RouteCollection
     */
    protected $router;

    /**
     * @var \callable
     */
    protected $exceptionDecorator;

    /**
     * @var array
     */
    protected $config = [];

    /**
     * @var array
     */
    protected $loggers = [];

    /**
     * @var ContainerInterface
     */
    protected $container;


    /**
     * @var \Zend\Diactoros\Response\EmitterInterface
     */
    protected $responseEmitter;


    /**
     * New Application.
     *
     * @param bool $debug Enable debug mode
     */
    public function __construct($debug = true)
    {
        $this->setConfig('debug', $debug);

        $this->setExceptionDecorator(function (\Exception $e) {

            $body = [
                'error' => [
                    'message' => $e->getMessage()
                ]
            ];

            if ($this->getConfig('debug', true) === true) {
                $body['error']['trace'] = explode(PHP_EOL, $e->getTraceAsString());
            }

            $response = new JsonResponse($body, method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500, [
                'Content-Type' => 'application/json'
            ]);

            return $response;
        });
    }

    /**
     * Set a container.
     *
     * @param \League\Container\ContainerInterface $container
     */
    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
        $this->container->share('app', $this);
        $this->router = null;
    }

    /**
     * Get the container.
     *
     * @return \League\Container\ContainerInterface
     */
    public function getContainer()
    {
        if (!isset($this->container)) {
            $this->setContainer(new Container);
        }

        return $this->container;
    }

    /**
     * Return the router.
     *
     * @return \League\Route\RouteCollection
     */
    public function getRouter()
    {
        if (!isset($this->router)) {
            $this->router = new RouteCollection($this->getContainer());
        }

        return $this->router;
    }

    /**
     * Return the event emitter.
     *
     * @return \League\Event\Emitter
     */
    public function getEventEmitter()
    {
        return $this->getEmitter();
    }

    /**
     * Return a logger
     *
     * @param string $name
     * @return \Psr\Log\LoggerInterface
     */
    public function getLogger($name = 'default')
    {
        if (isset($this->loggers[$name])) {
            return $this->loggers[$name];
        }

        $logger = new Logger($name);
        $this->loggers[$name] = $logger;
        return $logger;
    }

    /**
     * Get response emitter
     *
     * @return \Zend\Diactoros\Response\EmitterInterface
     */
    public function getResponseEmitter()
    {
        if(null === $this->responseEmitter){
            $this->responseEmitter = new Response\SapiEmitter();
        }
        return $this->responseEmitter;
    }

    /**
     * Set response emitter
     *
     * @param \Zend\Diactoros\Response\EmitterInterface $responseEmitter
     */
    public function setResponseEmitter($responseEmitter)
    {
        $this->responseEmitter = $responseEmitter;
    }

    /**
     * Set the exception decorator.
     *
     * @param callable $func
     *
     * @return void
     */
    public function setExceptionDecorator(callable $func)
    {
        $this->exceptionDecorator = $func;
    }

    /**
     * Add a GET route.
     *
     * @param string $route
     * @param mixed $action
     *
     * @return void
     */
    public function get($route, $action)
    {
        $this->getRouter()->map('GET', $route, $action);
    }

    /**
     * Add a POST route.
     *
     * @param string $route
     * @param mixed $action
     *
     * @return void
     */
    public function post($route, $action)
    {
        $this->getRouter()->map('POST', $route, $action);
    }

    /**
     * Add a PUT route.
     *
     * @param string $route
     * @param mixed $action
     *
     * @return void
     */
    public function put($route, $action)
    {
        $this->getRouter()->map('PUT', $route, $action);
    }

    /**
     * Add a DELETE route.
     *
     * @param string $route
     * @param mixed $action
     *
     * @return void
     */
    public function delete($route, $action)
    {
        $this->getRouter()->map('DELETE', $route, $action);
    }

    /**
     * Add a PATCH route.
     *
     * @param string $route
     * @param mixed $action
     *
     * @return void
     */
    public function patch($route, $action)
    {
        $this->getRouter()->map('PATCH', $route, $action);
    }

    /**
     * Handle the request.
     *
     * @param ServerRequestInterface $request
     * @param int $type
     * @param bool $catch
     *
     * @throws \Exception
     * @throws \LogicException
     *
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request, $type = self::MASTER_REQUEST, $catch = true)
    {

        // Passes the request to the container
        $this->getContainer()->add(ServerRequestInterface::class, $request);

        try {

            $this->emit('request.received', $request);

            $response = $this->getRouter()->dispatch(
                $request,
                new HtmlResponse('')
            );

            $this->emit('response.created', $request, $response);

        } catch (\Exception $e) {

            if (!$catch) {
                throw $e;
            }

            $response = call_user_func($this->exceptionDecorator, $e);
            if (!$response instanceof ResponseInterface) {
                throw new \LogicException('Exception decorator did not return an instance of ' . ResponseInterface::class);
            }

            $this->emit('response.created', $request, $response);

        }

        return $response;
    }

    /**
     * Terminates a request/response cycle.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     *
     * @return void
     */
    public function terminate(ServerRequestInterface $request, ResponseInterface $response)
    {
        $this->emit('response.sent', $request, $response);
    }

    /**
     * Run the application end execute final handler.
     *
     * @param ServerRequestInterface|null $request
     *
     * @throws \Exception
     */
    public function run(ServerRequestInterface $request = null)
    {
        if (null === $request) {
            $request = ServerRequestFactory::fromGlobals();
        }

        $response = $this->handle($request);
        $this->getResponseEmitter()->emit($response);

        $this->terminate($request, $response);
    }

    /**
     * Subscribe to an event.
     *
     * @param string $event
     * @param callable $listener
     * @param int $priority
     */
    public function subscribe($event, $listener, $priority = ListenerAcceptorInterface::P_NORMAL)
    {
        $this->addListener($event, $listener, $priority);
    }

    /**
     * Array Access get.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function offsetGet($key)
    {
        return $this->getContainer()->get($key);
    }

    /**
     * Array Access set.
     *
     * @param string $key
     * @param mixed $value
     *
     * @return void
     */
    public function offsetSet($key, $value)
    {
        $this->getContainer()->share($key, $value);
    }

    /**
     * Array Access unset.
     *
     * Does nothing since support for unset
     * shares is disabled in league/container 2
     *
     * @param string $key
     *
     * @return void
     */
    public function offsetUnset($key)
    {
    }

    /**
     * Array Access isset.
     *
     * @param string $key
     *
     * @return bool
     */
    public function offsetExists($key)
    {
        return $this->getContainer()->has($key);
    }

    /**
     * Register a new service provider
     *
     * @param $serviceProvider
     */
    public function register($serviceProvider)
    {
        $this->getContainer()->addServiceProvider($serviceProvider);
    }

    /**
     * Set a config item
     *
     * @param string $key
     * @param mixed $value
     */
    public function setConfig($key, $value)
    {
        $this->config[$key] = $value;
    }

    /**
     * Get a config key's value
     *
     * @param string $key
     * @param mixed $default
     *
     * @return mixed
     */
    public function getConfig($key, $default = null)
    {
        return isset($this->config[$key]) ? $this->config[$key] : $default;
    }
}
