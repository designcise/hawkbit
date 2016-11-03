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

namespace Hawkbit;

use Hawkbit\Application\AbstractApplication;
use Hawkbit\Application\Providers\MonologServiceProvider;
use Hawkbit\Application\Providers\WhoopsServiceProvider;
use League\Container\ReflectionContainer;
use League\Container\ServiceProvider\ServiceProviderInterface;
use League\Route\Http\Exception\NotFoundException;
use League\Route\RouteCollection;
use League\Route\RouteCollectionInterface;
use League\Route\RouteCollectionMapTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Hawkbit\Application\HttpApplicationEvent;
use Hawkbit\Application\MiddlewareRunner;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequestFactory;

/**
 * Hawkbit Application Class.
 */
final class Application extends AbstractApplication implements RouteCollectionInterface, TerminableInterface
{

    use RouteCollectionMapTrait;

    /**
     * @var \League\Route\RouteCollection
     */
    protected $router;

    /**
     * @var \Zend\Diactoros\Response\EmitterInterface
     */
    protected $responseEmitter;

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
     * Flag which forces response emitting and ignores already sended output
     *
     * @var bool
     */
    private $forceResponseEmitting = false;

    /**
     * Get content type of current request or response
     *
     * @var string
     */
    private $contentType = 'text/html';

    /**
     * @var callable[]
     */
    private $middlewares = [];

    /**
     * @var string
     */
    protected $applicationEventClass = HttpApplicationEvent::class;

    /**
     * New Application.
     *
     * @param bool|array $configuration Enable debug mode
     * @param ServiceProviderInterface[] $defaultProviders
     */
    public function __construct($configuration = [], array $defaultProviders = [
        MonologServiceProvider::class,
        WhoopsServiceProvider::class
    ])
    {
        $this->init($configuration);

        foreach ($defaultProviders as $provider){
            $this->getContainer()->addServiceProvider($provider);
        }
    }

    /**
     * @param $configuration
     */
    public function init($configuration = [])
    {
        $this->initConfiguration($configuration);
        $this->initContentType();
        $this->initSystem();
    }

    /*******************************************
     *
     *           Middleware
     *
     */

    /**
     * Add a middleware
     *
     * @param $middleware
     */
    public function addMiddleware(callable $middleware)
    {
        $this->middlewares[] = $middleware;
    }

    /**
     * @return callable[]
     */
    public function getMiddlewares()
    {
        return $this->middlewares;
    }

    /*******************************************
     *
     *           GETTER / SETTER
     *
     */

    /**
     * Get the Emitter.
     *
     * @deprecated
     * @return \League\Event\EmitterInterface
     */
    public function getEmitter()
    {
        return $this->getEventEmitter();
    }

    /**
     * Return the router.
     *
     * @return \League\Route\RouteCollection
     */
    public function getRouter()
    {
        if (!isset($this->router)) {
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
        if (!$this->getContainer()->has(ServerRequestInterface::class)) {
            $this->getContainer()->share(ServerRequestInterface::class, ServerRequestFactory::fromGlobals()->withHeader('content-type', $this->getContentType()));
        }

        /** @var ServerRequestInterface $request */
        $request = $this->validateContract($this->getContainer()->get(ServerRequestInterface::class),
            ServerRequestInterface::class);
        return $request;
    }

    /**
     * Get the response
     *
     * @param string $content
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function getResponse($content = '')
    {
        //transform content by content type
        if ($this->isAjaxRequest() || $this->isJsonRequest()) {
            if ($content instanceof Response\JsonResponse) {
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
                $class = Response\TextResponse::class;
            } elseif ($this->isJsonRequest()) {
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
        foreach ($request->getHeader($contentTypeKey) as $contentType) {
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
        if (!$this->getContainer()->has(Response\EmitterInterface::class)) {
            $this->getContainer()->share(Response\EmitterInterface::class, new Response\SapiEmitter());
        }

        /** @var Response\EmitterInterface $contract */
        $contract = $this->validateContract($this->getContainer()->get(Response\EmitterInterface::class),
            Response\EmitterInterface::class);
        return $contract;
    }

    /**
     * @return string
     */
    public function getContentType()
    {
        return $this->contentType;
    }

    /**
     * @param string $contentType
     * @return Application
     */
    public function setContentType($contentType)
    {
        $this->contentType = $contentType;
        return $this;
    }

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
     * Check if request is a ajax request
     *
     * @return bool
     */
    public function isJsonRequest()
    {
        return
            false !== strpos(
                $this->getContentType(),
                'json'
            ) && $this->isHttpRequest();
    }

    /**
     * Check if request is a ajax request
     *
     * @return bool
     */
    public function isSoapRequest()
    {
        return
            false !== strpos(
                $this->getContentType(),
                'soap'
            ) && $this->isHttpRequest();
    }

    /**
     * Check if request is a ajax request
     *
     * @return bool
     */
    public function isXmlRequest()
    {
        return
            false !== strpos(
                $this->getContentType(),
                'xml'
            ) && $this->isHttpRequest();
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
     * @return $this
     */
    public function setCanTerminate($terminate)
    {
        $this->terminate = $terminate;
        return $this;
    }

    /**
     * @return boolean
     */
    public function canForceResponseEmitting()
    {
        return $this->forceResponseEmitting;
    }

    /**
     * @param boolean $forceResponseEmitting
     * @return $this
     */
    public function setForceResponseEmitting($forceResponseEmitting)
    {
        $this->forceResponseEmitting = $forceResponseEmitting;
        return $this;
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
        return $this->getRouter()->map($method, $route, $this->bindClosureToInstance($action, $this));
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
        return $this->getRouter()->group($prefix, $this->bindClosureToInstance($group, $this));
    }

    /*******************************************
     *
     *         LIFECYCLE INVOCATION
     *
     */

    /**
     * Convert request into response. If an error occurs, Hawkbit tries
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
    )
    {
        // Passes the request to the container
        if (!$this->getContainer()->has(ServerRequestInterface::class)) {
            $this->getContainer()->share(ServerRequestInterface::class, $request);
        }

        //inject request content type
        $this->setContentType(ServerRequestFactory::getHeader('content-type', $this->getRequest()->getHeaders(), ''));

        if ($response === null) {
            $response = $this->getResponse();
        }

        $applicationEvent = $this->getApplicationEvent();
        $applicationEvent->setRequest($request);
        $applicationEvent->setResponse($response);

        // init middleware runner
        $middlewareRunner = new MiddlewareRunner($this->getMiddlewares());

        // add request handler middleware
        $middlewareRunner->addMiddleware(function (ServerRequestInterface $request, $response, $next) {
            return $next($this->handleRequest($request), $response);
        });

        // fetch response
        $response = $middlewareRunner->run([$request, $response],
            function ($request, $response) {
                return $this->handleResponse($request, $response);
            },
            function ($exception, $request, $response) use ($catch) {
                $notFoundException = $exception instanceof NotFoundException;
                $response = $this->handleError($exception, $request, $response, $catch)
                    ->withStatus($notFoundException ? 404 : 500);

                return $response;
            }
        );

        // validate response
        $response = $this->validateContract($response, ResponseInterface::class);

        // update content type
        $this->setContentType(ServerRequestFactory::getHeader('content-type', $response->getHeaders(), ''));

        return $response;
    }

    /**
     * Convert request into response.
     *
     * @param ServerRequestInterface $request
     * @return ServerRequestInterface
     */
    public function handleRequest(ServerRequestInterface $request)
    {
        $applicationEvent = $this->getApplicationEvent();
        $applicationEvent->setName(self::EVENT_REQUEST_RECEIVED);
        $this->emit($applicationEvent, $request);

        return $applicationEvent->getRequest();
    }

    /**
     * Handle Response
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function handleResponse(ServerRequestInterface $request, ResponseInterface $response)
    {
        $applicationEvent = $this->getApplicationEvent();
        $applicationEvent->setName(self::EVENT_RESPONSE_CREATED);
        $applicationEvent->setResponse($this->getRouter()->dispatch(
            $request,
            $response
        ));

        $this->emit($applicationEvent, $request, $response);

        return $applicationEvent->getResponse();
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
    )
    {
        // notify app that an error occurs
        $this->error = true;

        $errorHandler = $this->getErrorHandler();
        $exception = $errorHandler->decorateException($exception);

        // if delivered value of $catch, then configured value, then default value
        $catch = self::DEFAULT_ERROR_CATCH !== $catch ? $catch : $this->getConfig(self::KEY_ERROR_CATCH, $catch);

        $showError = $this->getConfig(self::KEY_ERROR, static::DEFAULT_ERROR);
        if (
            false === $catch
            && true === $showError
        ) {
            $this->throwException($exception);
        }

        $message = $errorHandler->getErrorMessage($exception);

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
        if ($request === null) {
            $request = $this->getRequest();
        }
        $response = $this->handle($request, $response);

        $this->emitResponse($request, $response);

        if ($this->canTerminate()) {
            $this->terminate($request, $response);
        }

        $this->shutdown($response);

        return $this;
    }

    /**
     * Emit a response
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @throws \Exception
     */
    public function emitResponse(ServerRequestInterface $request, ResponseInterface $response)
    {
        try {
            $this->getResponseEmitter()->emit($response);
        } catch (\Exception $e) {
            if ($this->canForceResponseEmitting()) {
                // flush buffers
                $maxBufferLevel = ob_get_level();

                while (ob_get_level() > $maxBufferLevel) {
                    ob_end_flush();
                }

                // print response
                echo $response->getBody();
            } else {
                throw $e;
            }
        }
        $applicationEvent = $this->getApplicationEvent();
        $applicationEvent->setName(self::EVENT_RESPONSE_SENT);
        $applicationEvent->setRequest($request);
        $applicationEvent->setResponse($response);
        $this->emit($applicationEvent);
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
        $applicationEvent = $this->getApplicationEvent();
        $applicationEvent->setRequest($request);
        $applicationEvent->setResponse($response);
        $applicationEvent->setName(self::EVENT_LIFECYCLE_COMPLETE);
        $this->emit($applicationEvent);

    }

    /**
     * Finish request. Collect garbage and terminate output buffering
     *
     * @param \Psr\Http\Message\ResponseInterface $response
     * @return void
     */
    public function shutdown($response = null)
    {

        $this->collectGarbage();
        $applicationEvent = $this->getApplicationEvent();
        $applicationEvent->setResponse($response);
        $applicationEvent->setName(self::EVENT_SYSTEM_SHUTDOWN);
        $this->emit($applicationEvent, $this->terminateOutputBuffering(1, $response));
    }

    /**
     * Close response stream and terminate output buffering
     *
     * @param int $level
     * @param null|\Psr\Http\Message\ResponseInterface $response
     * @return array
     */
    public function terminateOutputBuffering($level = 0, $response = null)
    {

        // close response stream berfore terminating output buffer
        // and only if response is an instance of
        // \Psr\Http\ResponseInterface
        if ($response instanceof ResponseInterface) {
            $body = $response->getBody();
            if ($body->isReadable()) {
                $body->close();
            }
        }

        // Command line output buffering is disabled in cli by default
        if ($this->isCli()) {
            return [];
        }

        // $level needs to be a numeric value
        if (!is_numeric($level)) {
            $level = 0;
        }

        // force type casting to an integer value
        if (!is_int($level)) {
            $level = (int)$level;
        }

        // avoid infinite loop on clearing
        // output buffer by set level to 0
        // if $level is smaller
        if (-1 > $level) {
            $level = 0;
        }

        // terminate all output buffers until $level is 0 or desired level
        // collect all contents and return
        $content = [];
        while (ob_get_level() > $level) {
            $content[] = ob_get_clean();
        }
        return $content;
    }

    /**
     * Perform garbage collection
     */
    public function collectGarbage()
    {
        // try to enable garbage collection
        if (!gc_enabled()) {
            @gc_enable();
        }

        // collect garbage only if garbage
        // collection is enabled
        if (gc_enabled()) {
            gc_collect_cycles();
        }
    }

    /**
     * Perform garbage collection
     * @deprecated
     */
    public function cleanUp()
    {
        return $this->collectGarbage();
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
    )
    {
        $applicationEvent = $this->getApplicationEvent();
        $applicationEvent->setName(self::EVENT_LIFECYCLE_ERROR);
        $applicationEvent->setRequest($request);
        $applicationEvent->setResponse($response);
        $applicationEvent->setErrorResponse($this->getResponse());

        $this->emit($applicationEvent, $exception);

        $errorResponse = $applicationEvent->getErrorResponse();

        if (!$errorResponse->getBody()->isWritable()) {
            return $errorResponse;
        }

        $content = $errorResponse->getBody()->__toString();
        if (empty($content)) {
            $errorResponse->getBody()->write($message);
        }

        return $errorResponse;
    }

    /**
     * @return HttpApplicationEvent
     */
    public function getApplicationEvent()
    {
        /** @var HttpApplicationEvent $applicationEvent */
        $applicationEvent = parent::getApplicationEvent();
        return $applicationEvent;
    }

    /**
     * @param $configuration
     */
    protected function initConfiguration($configuration)
    {
        if (is_bool($configuration)) {
            $this->setConfig(self::KEY_ERROR, $configuration);
        } elseif (
            is_array($configuration) ||
            ($configuration instanceof \ArrayAccess ||
                $configuration instanceof \Traversable)
        ) {
            $this->setConfig($configuration);
        }
    }

    /**
     *
     */
    protected function initContentType()
    {
        // configure request content type
        $this->setContentType(ServerRequestFactory::getHeader('content-type', ServerRequestFactory::fromGlobals()->getHeaders(), $this->getContentType()));
    }

    /**
     *
     */
    protected function initSystem(){
        // error handler
        set_error_handler(function($level, $message, $file = null, $line = null){
            $this->emit(self::EVENT_HANDLE_ERROR, [$level, $message, $file, $line]);
        });

        // exception handler
        set_exception_handler(function($exception){
            $this->emit(self::EVENT_SYSTEM_EXCEPTION, [$exception]);
        });

        // shutdown function
        register_shutdown_function(function (){
            $this->emit(self::EVENT_SYSTEM_SHUTDOWN);
        });
    }

}
