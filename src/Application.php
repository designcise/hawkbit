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

use League\Container\Container;
use League\Container\ContainerAwareInterface;
use League\Container\ContainerInterface;
use League\Container\Exception\NotFoundException;
use League\Container\ReflectionContainer;
use League\Event\Emitter;
use League\Event\EmitterInterface;
use League\Event\EmitterTrait;
use League\Event\ListenerAcceptorInterface;
use League\Route\RouteCollection;
use League\Route\RouteCollectionInterface;
use League\Route\RouteCollectionMapTrait;
use Monolog\Logger;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Turbine\Application\ConfiguratorInterface;
use Whoops\Handler\Handler;
use Whoops\Handler\HandlerInterface;
use Whoops\Handler\JsonResponseHandler;
use Whoops\Handler\PlainTextHandler;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequestFactory;

/**
 * Proton Application Class.
 */
class Application implements ApplicationInterface, ContainerAwareInterface, ListenerAcceptorInterface,
    RouteCollectionInterface, TerminableInterface, \ArrayAccess
{
    use EmitterTrait;
    use RouteCollectionMapTrait;

    /**
     * @var \League\Route\RouteCollection
     */
    protected $router;

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
     * Flag which is forcing response termination after
     * handling request / response lifecycle
     *
     * Response always destroyed before throwing exceptions!
     *
     * @var bool
     */
    private $terminate = true;

    /**
     * New Application.
     *
     * @param bool|array $configuration Enable debug mode
     */
    public function __construct($configuration = [])
    {
        if ( is_bool($configuration) ) {
            $this->setConfig(self::KEY_ERROR, $configuration);
        } elseif (
            is_array($configuration) ||
            ($configuration instanceof \ArrayAccess ||
                $configuration instanceof \Traversable)
        ) {
            $this->setConfig($configuration);
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

        $configurator = $this->getConfigurator();
        if ( is_array($key) || $key instanceof \Traversable || $key instanceof \ArrayAccess ) {
            $iter = new \ArrayIterator($key);
            while ($iter->valid()) {
                $this->setConfig($iter->key(), $iter->current());
                $iter->next();
            }
        } else {
            $this->validateConfigKey($key);
            $configurator[$key] = $value;
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

        $configurator = $this->getConfigurator();
        if ( $key === null ) {
            return $configurator;
        }

        $this->validateConfigKey($key);

        return $this->hasConfig($key) ? $configurator[$key] : $default;
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
        if ( null === $key ) {
            return false;
        }

        $this->validateConfigKey($key);
        $configurator = $this->getConfigurator();

        return isset($configurator[$key]);
    }

    /*******************************************
     *
     *           GETTER / SETTER
     *
     */

    /**
     * Get configuration container
     *
     * @return \ArrayAccess
     */
    public function getConfigurator()
    {
        if ( ! $this->getContainer()->has(ConfiguratorInterface::class) ) {
            $this->getContainer()->share(ConfiguratorInterface::class, \ArrayObject::class);
        }

        return $this->getContainer()->get(ConfiguratorInterface::class);
    }

    /**
     * Set a container.
     *
     * @param \League\Container\ContainerInterface $container
     * @return $this
     */
    public function setContainer(ContainerInterface $container)
    {
        $application = $this;
        $container->share(ApplicationInterface::class, $application);
        $container->share(\Interop\Container\ContainerInterface::class, $container);

        $this->container = $container;

        return $this;
    }

    /**
     * Get the container.
     *
     * @return \League\Container\Container
     */
    public function getContainer()
    {
        if ( ! isset($this->container) ) {
            $this->setContainer(new Container);
        }

        return $this->container;
    }

    /**
     * Get the Emitter.
     *
     * @return \League\Event\EmitterInterface
     */
    public function getEmitter()
    {
        if ( ! $this->getContainer()->has(EmitterInterface::class) ) {
            $this->getContainer()->share(EmitterInterface::class, new Emitter());
        }

        return $this->validateContract($this->getContainer()->get(EmitterInterface::class), EmitterInterface::class);
    }

    /**
     * @return \Whoops\Run
     */
    public function getErrorHandler()
    {
        if ( ! $this->getContainer()->has(Run::class) ) {
            $errorHandler = new Run();
            $errorHandler->pushHandler($this->getErrorResponseHandler());
            $errorHandler->pushHandler(function ($exception) {
                $this->emit(static::EVENT_RUNTIME_ERROR, [$exception]);

                return Handler::DONE;
            });
            $errorHandler->register();
            $this->getContainer()->share(Run::class, $errorHandler);
        }

        return $this->validateContract($this->getContainer()->get(Run::class), Run::class);
    }

    /**
     * Get the error response handler
     *
     * @return \Whoops\Handler\HandlerInterface
     */
    public function getErrorResponseHandler()
    {
        if ( ! $this->getContainer()->has(HandlerInterface::class) ) {
            if ( $this->isCli() ) {
                $class = PlainTextHandler::class;
            } elseif ( $this->isAjaxRequest() ) {
                $class = JsonResponseHandler::class;
            } else {
                $class = PrettyPageHandler::class;
            }
            $this->getContainer()->add(HandlerInterface::class, $class);
        }

        return $this->validateContract($this->getContainer()->get(HandlerInterface::class), HandlerInterface::class);
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
        if ( isset($this->loggers[$name]) ) {
            return $this->loggers[$name];
        }

        if ( ! $this->getContainer()->has(LoggerInterface::class) ) {
            $this->getContainer()->add(LoggerInterface::class, Logger::class);
        }

        $this->loggers[$name] = $this->getContainer()->get(LoggerInterface::class, [$name]);

        return $this->validateContract($this->loggers[$name], LoggerInterface::class);
    }

    /**
     * Return the router.
     *
     * @return \League\Route\RouteCollection
     */
    public function getRouter()
    {
        if ( ! isset($this->router) ) {
            $container = clone $this->getContainer();
            $container->delegate(new ReflectionContainer);
            $this->router = (new RouteCollection($container));
        }

        return $this->validateContract($this->router, RouteCollection::class);
    }

    /**
     * Get the request
     *
     * @return \Psr\Http\Message\ServerRequestInterface
     */
    public function getRequest()
    {
        if ( ! $this->getContainer()->has(ServerRequestInterface::class) ) {
            $this->getContainer()->share(ServerRequestInterface::class, ServerRequestFactory::fromGlobals());
        }

        return $this->validateContract($this->getContainer()->get(ServerRequestInterface::class),
            ServerRequestInterface::class);
    }

    /**
     * Get the response
     *
     * @param string $content
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function getResponse($content = '', $contentType = null)
    {
        //transform content by environment and request type
        if ( $this->isAjaxRequest() ) {
            if ( $content instanceof Response\JsonResponse ) {
                $content = json_decode($content->getBody());
            } elseif ( ! is_array($content) ) {
                $content = [$content];
            }
        } else {
            if ( is_array($content) ) {
                $content = implode('', $content);
            } elseif ( $content instanceof ResponseInterface ) {
                $content = $content->getBody()->__toString();
            }
        }
        if ( ! $this->getContainer()->has(ResponseInterface::class) ) {
            if ( $this->isCli() ) {
                $class = Response\TextResponse::class;
            } elseif ( $this->isAjaxRequest() ) {
                $class = Response\JsonResponse::class;

            } else {
                $class = Response\HtmlResponse::class;
            }
            $this->getContainer()->add(ResponseInterface::class, $class);
        }

        /** @var ResponseInterface $response */
        $response = $this->validateContract($this->getContainer()->get(ResponseInterface::class, [$content]),
            ResponseInterface::class);

        //inject request content type
        $request = $this->getRequest();

        $contentTypeKey = 'content-type';
        foreach ($request->getHeader($contentTypeKey) as $contentType){
            $response = $response->withHeader($contentTypeKey, $contentType);
        }

        return $response;
    }


    /**
     * Get response emitter
     *
     * @return \Zend\Diactoros\Response\EmitterInterface
     */
    public function getResponseEmitter()
    {
        if ( ! $this->getContainer()->has(Response\EmitterInterface::class) ) {
            $this->getContainer()->share(Response\EmitterInterface::class, new Response\SapiEmitter());
        }

        return $this->validateContract($this->getContainer()->get(Response\EmitterInterface::class),
            Response\EmitterInterface::class);
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
    public function isAjaxRequest()
    {
        return
            false !== strpos(
                strtolower(ServerRequestFactory::getHeader('x-requested-with', $this->getRequest()->getHeaders(), '')),
                'xmlhttprequest'
            ) && $this->isHttpRequest();
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
     * Check if an error has been occurred
     *
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
    public function isHttpRequest()
    {
        return ! $this->isCli();
    }

    /**
     * Check that terminate the response request
     * lifecycle is enabled
     *
     * @return boolean
     */
    public function canTerminate()
    {
        return $this->terminate;
    }

    /**
     * Set terminating flag
     *
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
     * Add a route to the map.
     *
     * @param $method
     * @param $route
     * @param $action
     *
     * @return \League\Route\Route
     */
    public function map($method, $route, $action)
    {
        return $this->getRouter()->map($method, $route, $this->bindClosureToInstance($action));
    }

    /**
     * Add a group of routes to the collection. Binds $this to app instance
     *
     * @param $prefix
     * @param callable $group
     *
     * @return \League\Route\RouteGroup
     */
    public function group($prefix, callable $group)
    {
        return $this->getRouter()->group($prefix, $this->bindClosureToInstance($group));
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
     *
     * @deprecated
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
     * Removing services are not support by
     * `league/container` 2.0 and greater
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
     *         CONTRACTS
     *
     */

    /**
     * throw a exception
     *
     * @param \Throwable|\Exception $exception
     *
     * @throws \Throwable|\Exception
     */
    public function throwException($exception)
    {
        $this->finishRequest();
        throw $exception;
    }

    /**
     * Validates that class is instance of contract
     *
     * @param $class
     * @param $contract
     *
     * @return string|object
     *
     * @throws \InvalidArgumentException|\LogicException
     */
    public function validateContract($class, $contract)
    {
        $validateObject = function ($object) {
            //does need trigger when calling *_exists with object
            $condition = is_string($object) ? class_exists($object) || interface_exists($object) : is_object($object);
            if ( false === $condition ) {
                $this->throwException(new \InvalidArgumentException('Class not exists ' . $object));
            }
        };

        $convertClassToString = function ($object) {
            if ( is_object($object) ) {
                $object = get_class($object);
            }

            return is_string($object) ? $object : false;
        };

        $validateObject($class);
        $validateObject($contract);

        if ( ! ($class instanceof $contract) ) {

            if ( is_object($class) ) {
                $class = get_class($class);
            }
            $this->throwException(new \LogicException($convertClassToString($class) . ' needs to be an instance of ' . $convertClassToString($contract)));
        }

        return $class;
    }

    /*******************************************
     *
     *         LIFECYCLE INVOCATION
     *
     */

    /**
     * Convert request into response. If an error occurs, turbine tries
     * to handle error as response.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param bool $catch
     *
     * @return ResponseInterface
     *
     * @throws \Throwable
     */
    public function handle(
        ServerRequestInterface $request,
        ResponseInterface $response = null,
        $catch = self::DEFAULT_ERROR_CATCH
    ) {

        // Passes the request to the container
        $this->getContainer()->share(ServerRequestInterface::class, $request);

        if ( $response === null ) {
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
     * Convert request into response.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function handleRequest(ServerRequestInterface $request, ResponseInterface $response)
    {
        $this->emit(self::EVENT_REQUEST_RECEIVED, $request);

        $response = $this->getRouter()->dispatch(
            $request,
            $response
        );

        $this->emit(self::EVENT_RESPONSE_CREATED, $request, $response);

        return $response;
    }

    /**
     * Handle error and return response of error message or throw
     * error if error.catch is disabled.
     *
     * @param \Throwable|\Exception $exception
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param bool $catch
     *
     * @return \Psr\Http\Message\ResponseInterface
     *
     * @throws string
     */
    public function handleError(
        $exception,
        ServerRequestInterface $request,
        ResponseInterface $response,
        $catch = self::DEFAULT_ERROR_CATCH
    ) {
        $exception = $this->decorateException($exception);
        $errorHandler = $this->getErrorHandler();

        //if delivered value of $catch, then configured value, then default value
        $catch = self::DEFAULT_ERROR_CATCH !== $catch ? $catch : $this->getConfig(self::KEY_ERROR_CATCH, $catch);

        if (
            false === $this->getConfig(self::KEY_ERROR_CATCH, $catch)
            && false === $this->getConfig(self::KEY_ERROR, static::DEFAULT_ERROR)
        ) {
            $this->throwException($exception);
        }

        $message = $this->determineErrorMessage($exception, $errorHandler);

        return $this->determineErrorResponse($exception, $message, $response, $request);
    }

    /**
     * Handle response / request lifecycle
     *
     * When $callable is a valid callable, callable will executed before emit response
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request A Request instance
     * @param \Psr\Http\Message\ResponseInterface $response A response instance
     *
     * @return $this
     *
     */
    public function run(ServerRequestInterface $request = null, ResponseInterface $response = null)
    {
        if ( $request === null ) {
            $request = $this->getRequest();
        }

        if ( $response === null ) {
            $response = $this->getResponse();
        }

        $response = $this->handle($request, $response);

        $this->emitResponse($request, $response);

        if ( $this->canTerminate() ) {
            $this->terminate($request, $response);
        }

        return $this;
    }

    /**
     * Emit a response
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     */
    public function emitResponse(ServerRequestInterface $request, ResponseInterface $response)
    {
        $this->getResponseEmitter()->emit($response);
        $this->emit(self::EVENT_RESPONSE_SENT, $request, $response);
    }

    /**
     * Terminates a request/response cycle.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     *
     * @return void
     */
    public function terminate(ServerRequestInterface $request, ResponseInterface $response)
    {
        $this->emit(self::EVENT_LIFECYCLE_COMPLETE, $request, $response);
        $this->finishRequest($response);
    }

    /**
     * Finish request. Collect garbage and terminate output buffering
     *
     * @param \Psr\Http\Message\ResponseInterface $response
     * @return void
     */
    public function finishRequest($response = null)
    {
        $this->terminateOutputBuffering(0, $response);
        $this->cleanUp();
    }

    /**
     * Close response stream and terminate output buffering
     *
     * @param int $level
     * @param null|\Psr\Http\Message\ResponseInterface $response
     */
    public function terminateOutputBuffering($level = 0, $response = null)
    {

        // close response stream berfore terminating output buffer
        // and only if response is an instance of
        // \Psr\Http\ResponseInterface
        if ( $response instanceof ResponseInterface ) {
            $body = $response->getBody();
            if ( $body->isReadable() ) {
                $body->close();
            }
        }

        // Command line output buffering is disabled in cli by default
        if ( $this->isCli() ) {
            return;
        }

        // $level needs to be a numeric value
        if ( ! is_numeric($level) ) {
            $level = 0;
        }

        // force type casting to an integer value
        if ( ! is_int($level) ) {
            $level = (int)$level;
        }

        // avoid infinite loop on clearing
        // output buffer by set level to 0
        // if $level is smaller
        if ( -1 > $level ) {
            $level = 0;
        }

        // terminate all output buffers until $level is 0 or desired level
        while (ob_get_level() > $level) {
            ob_end_clean();
        }
    }

    /**
     * Perform garbage collection
     */
    public function cleanUp()
    {
        // try to enable garbage collection
        if ( ! gc_enabled() ) {
            @gc_enable();
        }

        // collect garbage only if garbage
        // collection is enabled
        if ( gc_enabled() ) {
            gc_collect_cycles();
        }
    }

    /**
     * Convert any type into an exception
     *
     * @param $error
     * @return \Exception|string
     */
    private function decorateException($error)
    {

        if ( is_callable($error) ) {
            $error = $this->getContainer()->call($error, [$this->getRequest(), $this->getResponse()]);
        }

        if ( is_object($error) && ! ($error instanceof \Exception) ) {
            $error = method_exists($error,
                '__toString') ? $error->__toString() : 'Error with object ' . get_class($error);
        }

        if ( is_resource($error) ) {
            $error = 'Error with resource type ' . get_resource_type($error);
        }

        if ( is_array($error) ) {
            $error = implode("\n", $error);
        }

        if ( ! ($error instanceof \Exception) ) {
            $error = new \Exception(is_scalar($error) ? $error : 'Error with ' . gettype($error));
        }

        return $error;
    }

    /**
     * Determine error message by error configuration
     *
     * @param \Throwable $exception
     * @param \Whoops\Run $errorHandler
     *
     * @return string
     *
     */
    private function determineErrorMessage($exception, $errorHandler)
    {
        if ( false === $this->getConfig(self::KEY_ERROR, static::DEFAULT_ERROR) ) {
            $message = $exception->getMessage();
        } else {
            $errorHandler->allowQuit($this->error);

            $method = $errorHandler::EXCEPTION_HANDLER;
            ob_start();
            $errorHandler->$method($exception);
            $message = ob_get_clean();
        }

        return $message;
    }

    /**
     * Determines response for error. Emits `lifecycle.error` event.
     *
     * @param \Throwable $exception
     * @param string $message
     * @param ResponseInterface $response
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    private function determineErrorResponse(
        $exception,
        $message,
        ResponseInterface $response,
        ServerRequestInterface $request
    ) {
        $errorResponse = $this->getResponse();
        $this->emit(self::EVENT_LIFECYCLE_ERROR, $exception, $request, $errorResponse, $response);
        $this->error = true;

        if ( ! $errorResponse->getBody()->isWritable() ) {
            return $errorResponse;
        }

        $content = $errorResponse->getBody()->__toString();
        if ( empty($content) ) {
            $errorResponse->getBody()->write($message);
        }

        return $errorResponse;
    }

    /**
     * Validate that config key is scalar
     *
     * @param $key
     * @throws \Throwable
     */
    private function validateConfigKey($key)
    {
        if ( ! is_scalar($key) ) {
            $this->throwException(new \InvalidArgumentException('Key needs to be a valid scalar!'));
        }
    }

    /**
     * Bind any closure to application instance
     *
     * @param $closure
     *
     * @return mixed
     */
    private function bindClosureToInstance($closure)
    {
        if ( $closure instanceof \Closure ) {
            \Closure::bind($closure, $this, get_class($this));
        }

        return $closure;
    }
}
