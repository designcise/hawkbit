<?php
/**
 * The Proton Micro Framework.
 *
 * @author  Alex Bilbie <hello@alexbilbie.com>
 * @author  Marco Bunge <marco_bunge@web.de>
 *
 * @license MIT
 */

namespace Turbine;

use League\Container\ContainerAwareInterface;
use League\Container\ContainerInterface;
use League\Container\Exception\NotFoundException;
use League\Container\ReflectionContainer;
use League\Event\Emitter;
use League\Event\EmitterInterface;
use League\Event\EmitterTrait;
use League\Event\ListenerAcceptorInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use League\Container\Container;
use League\Route\RouteCollection;
use Monolog\Logger;
use Whoops\Handler\HandlerInterface;
use Whoops\Handler\JsonResponseHandler;
use Whoops\Handler\PlainTextHandler;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;
use Zend\Diactoros\Response;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\Response\TextResponse;
use Zend\Diactoros\ServerRequestFactory;

/**
 * Proton Application Class.
 */
class Application implements ApplicationInterface, ContainerAwareInterface, ListenerAcceptorInterface, TerminableInterface, \ArrayAccess
{
    const CONFIG_KEY_ERROR_CATCH = 'error.catch';
    const KEY_ERROR = 'error';
    use EmitterTrait;

    const DEFAULT_ERROR = true;
    const DEFAULT_ERROR_CATCH = true;

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
     * Set while handle exception.
     * @var bool
     */
    private $error = false;

    /**
     * Flag which is forcing termination after handling request / response lifecycle
     * @var bool
     */
    private $terminate = false;

    /**
     * New Application.
     *
     * @param bool|array $configuration Enable debug mode
     */
    public function __construct($configuration = [])
    {
        if (is_bool($configuration)) {
            $this->setConfig(self::KEY_ERROR, $configuration);
        } elseif (is_array($configuration)) {
            foreach ($configuration as $key => $value) {
                $this->setConfig($key, $value);
            }
        }
    }

    /*******************************************
     *
     *               CONFIG
     *
     */

    /**
     * Set a config item. Add recursive if key is traversable.
     *
     * @param string|array|\Traversable $key
     * @param mixed $value
     *
     * @return $this
     */
    public function setConfig($key, $value = null)
    {
        if (is_array($key) || $key instanceof \Traversable) {
            $config = $key;
            foreach ($config as $key => $value) {
                $this->setConfig($key, $value);
            }
        } else {
            $this->config[$key] = $value;
        }

        return $this;
    }

    /**
     * Get a config key's value
     *
     * @param string $key
     * @param mixed $default
     *
     * @return mixed
     */
    public function getConfig($key = null, $default = null)
    {
        if ($key === null) {
            return $this->config;
        }

        return $this->hasConfig($key) ? $this->config[$key] : $default;
    }

    /**
     * Check if key exists
     *
     * @param $key
     *
     * @return bool
     */
    public function hasConfig($key)
    {
        return isset($this->config[$key]);
    }

    /*******************************************
     *
     *           GETTER / SETTER
     *
     */

    /**
     * Set a container.
     *
     * @param \League\Container\ContainerInterface $container
     * @return $this
     */
    public function setContainer(ContainerInterface $container)
    {
        if ($container instanceof Container) {
            $container->delegate(
                new ReflectionContainer
            );
        }
        $application = $this;
        $container->share(ApplicationInterface::class, $application);

        $this->container = $container;
        $this->router = null;

        return $this;
    }

    /**
     * Get the container.
     *
     * @return \League\Container\Container
     */
    public function getContainer()
    {
        if (!isset($this->container)) {
            $this->setContainer(new Container);
        }

        return $this->container;
    }

    /**
     * Get the Emitter.
     *
     * @return EmitterInterface
     */
    public function getEmitter()
    {
        if (!$this->emitter) {
            $this->emitter = new Emitter();
        }

        return $this->emitter;
    }

    /**
     * @return Run
     */
    public function getErrorHandler()
    {
        if(!$this->getContainer()->has(Run::class)){
            $errorHandler = new Run();
            $errorHandler->pushHandler($this->getErrorResponseHandler());
            $this->getContainer()->share(Run::class, $errorHandler);
        }
        return $this->getContainer()->get(Run::class);
    }

    /**
     * Get the response
     *
     * @return HandlerInterface
     */
    public function getErrorResponseHandler()
    {
        if (!$this->getContainer()->has(HandlerInterface::class)) {
            if ($this->isCli()) {
                $class = PlainTextHandler::class;
            } elseif ($this->isAjax()) {
                $class = JsonResponseHandler::class;
            } else {
                $class = PrettyPageHandler::class;
            }
            $this->getContainer()->add(HandlerInterface::class, $class);
        }

        return $this->getContainer()->get(HandlerInterface::class);
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
     * Return the router.
     *
     * @return \League\Route\RouteCollection
     */
    public function getRouter()
    {
        if (!isset($this->router)) {
            $this->router = (new RouteCollection($this->getContainer()));
        }

        return $this->router;
    }

    /**
     * Get the request
     *
     * @return ServerRequestInterface
     */
    public function getRequest()
    {
        if (!$this->getContainer()->has(ServerRequestInterface::class)) {
            $this->getContainer()->share(ServerRequestInterface::class, ServerRequestFactory::fromGlobals());
        }

        return $this->getContainer()->get(ServerRequestInterface::class);
    }

    /**
     * Get the response
     *
     * @param string $content
     * @return ResponseInterface
     */
    public function getResponse($content = '')
    {
        //transform content by environment and request type
        if ($this->isAjax()) {
            if ($content instanceof JsonResponse) {
                $content = json_decode($content->getBody());
            } elseif (!is_array($content)) {
                $content = [$content];
            }
        } else {
            if (is_array($content)) {
                $content = implode('', $content);
            } elseif ($content instanceof ResponseInterface) {
                $content = $content->getBody()->__toString();
            }
        }
        if (!$this->getContainer()->has(ResponseInterface::class)) {
            if ($this->isCli()) {
                $class = TextResponse::class;
            } elseif ($this->isAjax()) {
                $class = JsonResponse::class;

            } else {
                $class = HtmlResponse::class;
            }
            $this->getContainer()->add(ResponseInterface::class, $class);
        }

        return $this->getContainer()->get(ResponseInterface::class, [$content]);
    }


    /**
     * Get response emitter
     *
     * @return \Zend\Diactoros\Response\EmitterInterface
     */
    public function getResponseEmitter()
    {
        if (null === $this->responseEmitter) {
            $this->responseEmitter = new Response\SapiEmitter();
        }

        return $this->responseEmitter;
    }

    /*******************************************
     *
     *               STATUS
     *
     */

    /**
     * Check if request is a ajax request
     *
     * @return bool
     */
    public function isAjax()
    {
        return
            false !== strpos(
                strtolower(ServerRequestFactory::getHeader('x-requested-with', $this->getRequest()->getHeaders(), '')),
                'xmlhttprequest'
            ) && $this->isHttp();
    }

    /**
     * Check server environment for cli
     *
     * @return bool
     */
    public function isCli()
    {
        return php_sapi_name() === 'cli';
    }

    /**
     * @return boolean
     */
    public function isError()
    {
        return $this->error;
    }

    /**
     * Check server environment for http
     *
     * @return bool
     */
    public function isHttp()
    {
        return !$this->isCli();
    }

    /**
     * @return boolean
     */
    public function canTerminate()
    {
        return $this->terminate;
    }

    /**
     * @param boolean $terminate
     */
    public function setCanTerminate($terminate)
    {
        $this->terminate = $terminate;
    }

    /*******************************************
     *
     *               ROUTER
     *
     */

    /**
     * Add a GET route.
     *
     * @param string $route
     * @param mixed $action
     *
     * @return $this
     */
    public function get($route, $action)
    {
        $this->getRouter()->map('GET', $route, $action);

        return $this;
    }

    /**
     * Add a POST route.
     *
     * @param string $route
     * @param mixed $action
     *
     * @return $this
     */
    public function post($route, $action)
    {
        $this->getRouter()->map('POST', $route, $action);

        return $this;
    }

    /**
     * Add a PUT route.
     *
     * @param string $route
     * @param mixed $action
     *
     * @return $this
     */
    public function put($route, $action)
    {
        $this->getRouter()->map('PUT', $route, $action);

        return $this;
    }

    /**
     * Add a DELETE route.
     *
     * @param string $route
     * @param mixed $action
     *
     * @return $this
     */
    public function delete($route, $action)
    {
        $this->getRouter()->map('DELETE', $route, $action);

        return $this;
    }

    /**
     * Add a PATCH route.
     *
     * @param string $route
     * @param mixed $action
     *
     * @return $this
     */
    public function patch($route, $action)
    {
        $this->getRouter()->map('PATCH', $route, $action);

        return $this;
    }

    /*******************************************
     *
     *                Events
     *
     */

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

    /*******************************************
     *
     *                IoC
     *
     */

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

    /*******************************************
     *
     *         LIFECYCLE INVOCATION
     *
     */

    /**
     * Handle the request.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param bool $catch
     *
     * @return ResponseInterface
     *
     * @throws \Throwable
     */
    public function handle(ServerRequestInterface $request, ResponseInterface $response = null, $catch = self::DEFAULT_ERROR_CATCH)
    {

        // Passes the request to the container
        $this->getContainer()->share(ServerRequestInterface::class, $request);

        if($response === null){
            $response = $this->getResponse();
        }

        try {
            $response = $this->handleRequest($request, $response);
        } catch (\Exception $exception) {

            $response = $this->handleError($exception, $request, $response, $catch)
                ->withStatus($exception instanceof NotFoundException ? 404 : 500);
        }

        return $response;
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function handleRequest(ServerRequestInterface $request, ResponseInterface $response)
    {
        $this->emit('request.received', $request);

        $response = $this->getRouter()->dispatch(
            $request,
            $response
        );

        $this->emit('response.created', $request, $response);

        return $response;
    }

    /**
     * @param \Throwable|\Exception $exception
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param bool $catch
     *
     * @return ResponseInterface
     *
     * @throws string
     */
    public function handleError($exception, ServerRequestInterface $request, ResponseInterface $response, $catch = self::DEFAULT_ERROR_CATCH)
    {
        $exception = $this->decorateException($exception);
        $errorHandler = $this->getErrorHandler();

        //if delivered value of $catch, then configured value, then default value
        $catch = self::DEFAULT_ERROR_CATCH !== $catch ? $catch : $this->getConfig(self::CONFIG_KEY_ERROR_CATCH, $catch);

        if (false === $this->getConfig(self::KEY_ERROR, static::DEFAULT_ERROR)) {
            $message = $exception->getMessage();
        } else {
            $errorHandler->allowQuit($this->error);

            if (false === $this->getConfig(self::CONFIG_KEY_ERROR_CATCH, $catch)) {
                $errorHandler->register();
                $this->cleanUp();
                throw $exception;
            }

            $method = Run::EXCEPTION_HANDLER;
            ob_start();
            $errorHandler->$method($exception);
            $message = ob_get_clean();
        }


        $errorResponse = $this->getResponse();
        $this->emit('response.error',$exception, $request, $errorResponse, $response);
        $this->error = true;

        if(!$errorResponse->getBody()->isWritable()){
            return $errorResponse;
        }

        $content = $errorResponse->getBody()->__toString();
        if(empty($content)){
            $errorResponse->getBody()->write($message);
        }

        return $errorResponse;
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
        $this->emit('lifecycle.terminate', $request, $response);

        $body = $response->getBody();
        if ($body->isReadable()) {
            $body->close();
        }

        $this->cleanUp();
    }

    /**
     * Handle response / request lifecycle
     *
     * When $callable is a valid callable, callable will executed before emit response
     *
     * @param ServerRequestInterface $request A Request instance
     * @param ResponseInterface $response A response instance
     * @param null|callable $callable Call a handler before response is terminated
     * @return ResponseInterface
     *
     */
    public function run(ServerRequestInterface $request = null, ResponseInterface $response = null, $callable = null)
    {
        if ($request === null) {
            $request = $this->getRequest();
        }

        if ($response === null) {
            $response = $this->getResponse();
        }

        $response = $this->handle($request, $response);

        //call additional invokable or callable
        if (null !== $callable) {
            $response = $this->getContainer()->call($callable, [$request, $response]);
        }

        $this->emit('response.sent', $request, $response);

        $this->getResponseEmitter()->emit($response);

        return $response;
    }

    private function cleanUp()
    {
        if (!gc_enabled()) {
            gc_enable();
        }
        gc_collect_cycles();
    }

    /**
     * Convert any type into an exception
     *
     * @param $error
     * @return \Exception|string
     */
    private function decorateException($error)
    {

        if (is_callable($error)) {
            $error = $this->getContainer()->call($error, [$this->getRequest(), $this->getResponse()]);
        }

        if (is_object($error) && !($error instanceof \Exception)) {
            $error = method_exists($error, '__toString') ? $error->__toString() : 'Error with object ' . get_class($error);
        }

        if(is_resource($error)){
            $error = 'Error with resource type ' . get_resource_type($error);
        }

        if (is_array($error)) {
            $error = implode("\n", $error);
        }

        if (!($error instanceof \Exception)) {
            $error = new \Exception(is_scalar($error) ? $error : 'Error with ' . gettype($error));
        }

        return $error;
    }
}
