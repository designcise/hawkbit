<?php
/**
 * The Turbine Micro Framework. An advanced derivate of Proton Micro Framework
 *
 * @author Marco Bunge <marco_bunge@web.de>
 * @copyright Marco Bunge <marco_bunge@web.de>
 *
 * @license MIT
 */

namespace Turbine\Application;


use League\Event\AbstractEvent;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Turbine\Application;

class ApplicationEvent extends AbstractEvent
{
    /**
     * This event is fired when a request is received but before it has been processed by the router.
     */
    const EVENT_REQUEST_RECEIVED = 'request.received';

    /**
     * This event is fired when a response has been created but before it has been output.
     */
    const EVENT_RESPONSE_CREATED = 'response.created';

    /**
     * This event is fired when a response has been output.
     */
    const EVENT_RESPONSE_SENT = 'response.sent';

    /**
     * This event is fired only when an error occurs while handling request/response lifecycle.
     * This event is fired after `runtime.error`
     */
    const EVENT_LIFECYCLE_ERROR = 'lifecycle.error';

    /**
     * This event is always fired when an error occurs.
     */
    const EVENT_RUNTIME_ERROR = 'runtime.error';

    /**
     * This event is fired before completing application lifecycle.
     */
    const EVENT_LIFECYCLE_COMPLETE = 'lifecycle.complete';

    /**
     * This event is fired on each shutdown.
     */
    const EVENT_SHUTDOWN = 'shutdown';

    /**
     * @var Application
     */
    private $application;
    /**
     * @var ServerRequestInterface
     */
    private $request;
    /**
     * @var ResponseInterface
     */
    private $response;
    /**
     * @var ResponseInterface
     */
    private $errorResponse;
    /**
     * @var string
     */
    private $name;

    /**
     * ApplicationEvent constructor.
     * @param string $name
     * @param Application $application
     */
    public function __construct($name, Application $application)
    {
        $this->name = $name;
        $this->application = $application;
    }

    /**
     * @return Application
     */
    public function getApplication()
    {
        return $this->application;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return ServerRequestInterface
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @param ServerRequestInterface $request
     * @return ApplicationEvent
     */
    public function setRequest($request)
    {
        $this->request = $request;
        return $this;
    }

    /**
     * @return ResponseInterface
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @param ResponseInterface $response
     * @return ApplicationEvent
     */
    public function setResponse($response)
    {
        $this->response = $response;
        return $this;
    }

    /**
     * @return ResponseInterface
     */
    public function getErrorResponse()
    {
        return $this->errorResponse;
    }

    /**
     * @param ResponseInterface $errorResponse
     * @return ApplicationEvent
     */
    public function setErrorResponse($errorResponse)
    {
        $this->errorResponse = $errorResponse;
        return $this;
    }

    /**
     * delegate previous application event data
     * @param $name
     * @param ApplicationEvent $previous
     * @return ApplicationEvent
     */
    public function delegate($name, ApplicationEvent $previous){
        $event = clone $previous;

        $nameProp = new \ReflectionProperty($event, 'name');
        $nameProp->setAccessible(true);
        $nameProp->setValue($event, $name);
        $nameProp->setAccessible(false);

        return $event;
    }

}