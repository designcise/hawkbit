<?php
/**
 * The Proton Micro Framework.
 *
 * @author  Alex Bilbie <hello@alexbilbie.com>
 * @license MIT
 */

namespace Proton;

use League\Event\EmitterTrait;
use League\Event\ListenerAcceptorInterface;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\TerminableInterface;
use League\Container\Container;
use League\Route\RouteCollection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Proton\Events;

/**
 * Proton Application Class.
 */
class Application implements HttpKernelInterface, TerminableInterface, \ArrayAccess
{
    use EmitterTrait;

    /**
     * @var \League\Route\RouteCollection
     */
    protected $router;

    /**
     * @var \League\Container\Container
     */
    protected $container;

    /**
     * @var \callable
     */
    protected $exceptionDecorator;

    /**
     * New Application.
     */
    public function __construct()
    {
        $this->setContainer(new Container);
        $this->container->singleton('app', $this);
        $this->container->add('debug', false);

        $this->router = new RouteCollection($this->container);

        $this->setExceptionDecorator(function (\Exception $e) {
            $response = new Response;
            $response->setStatusCode(method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500);
            $response->headers->add(['Content-Type' => 'application/json']);

            $return = [
                'error' => [
                    'message' => $e->getMessage()
                ]
            ];

            if ($this['debug'] === true) {
                $return['error']['trace'] = explode(PHP_EOL, $e->getTraceAsString());
            }

            $response->setContent(json_encode($return));

            return $response;
        });
    }

    /**
     * Returns the DI container.
     *
     * @return \League\Container\Container
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * Set the container
     *
     * @param \League\Container\Container $container
     */
    public function setContainer(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Return the router.
     *
     * @return \League\Route\RouteCollection
     */
    public function getRouter()
    {
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
     * @param mixed  $action
     *
     * @return void
     */
    public function get($route, $action)
    {
        $this->router->addRoute('GET', $route, $action);
    }

    /**
     * Add a POST route.
     *
     * @param string $route
     * @param mixed  $action
     *
     * @return void
     */
    public function post($route, $action)
    {
        $this->router->addRoute('POST', $route, $action);
    }

    /**
     * Add a PUT route.
     *
     * @param string $route
     * @param mixed  $action
     *
     * @return void
     */
    public function put($route, $action)
    {
        $this->router->addRoute('PUT', $route, $action);
    }

    /**
     * Add a DELETE route.
     *
     * @param string $route
     * @param mixed  $action
     *
     * @return void
     */
    public function delete($route, $action)
    {
        $this->router->addRoute('DELETE', $route, $action);
    }

    /**
     * Add a PATCH route.
     *
     * @param string $route
     * @param mixed  $action
     *
     * @return void
     */
    public function patch($route, $action)
    {
        $this->router->addRoute('PATCH', $route, $action);
    }

    /**
     * Handle the request.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param int                                       $type
     * @param bool                                      $catch
     *
     * @throws \Exception
     * @throws \LogicException
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = true)
    {
        // Passes the request to the container
        $this->container->add('Symfony\Component\HttpFoundation\Request', $request);

        try {

            $this->emit(
                (new Events\RequestReceivedEvent($request))
            );

            $dispatcher = $this->router->getDispatcher();
            $response = $dispatcher->dispatch(
                $request->getMethod(),
                $request->getPathInfo()
            );

            $this->emit(
                (new Events\ResponseBeforeEvent($request, $response))
            );

            return $response;

        } catch (\Exception $e) {

            if (!$catch) {
                throw $e;
            }

            $response = call_user_func($this->exceptionDecorator, $e);
            if (!$response instanceof Response) {
                throw new \LogicException('Exception decorator did not return an instance of Symfony\Component\HttpFoundation\Response');
            }

            $this->emit(
                (new Events\ResponseBeforeEvent($request, $response))
            );

            return $response;
        }
    }

    /**
     * Terminates a request/response cycle.
     *
     * @param \Symfony\Component\HttpFoundation\Request  $request
     * @param \Symfony\Component\HttpFoundation\Response $response
     *
     * @return void
     */
    public function terminate(Request $request, Response $response)
    {
        $this->emit(
            (new Events\ResponseAfterEvent($request, $response))
        );
    }

    /**
     * Run the application.
     *
     * @param \Symfony\Component\HttpFoundation\Request|null $request
     *
     * @return void
     */
    public function run(Request $request = null)
    {
        if (null === $request) {
            $request = Request::createFromGlobals();
        }

        $response = $this->handle($request);
        $response->send();

        $this->terminate($request, $response);
    }

    /**
     * Subscribe to an event.
     *
     * @param string   $event
     * @param callable $listener
     * @param int      $priority
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
        return $this->container->get($key);
    }

    /**
     * Array Access set.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return void
     */
    public function offsetSet($key, $value)
    {
        $this->container->singleton($key, $value);
    }

    /**
     * Array Access unset.
     *
     * @param string $key
     *
     * @return void
     */
    public function offsetUnset($key)
    {
        $this->container->offsetUnset($key);
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
        return $this->container->isRegistered($key);
    }
}
