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

namespace Turbine\Application;


use League\Event\AbstractEvent;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Turbine\ApplicationInterface;
use Zend\Stdlib\ArrayObject;

class ApplicationEvent extends AbstractEvent
{

    /**
     * @var
     */
    private $response;

    /**
     * @var
     */
    private $request;

    /**
     * @var
     */
    private $errorResponse;

    /**
     * @var
     */
    private $name;
    /**
     * @var ApplicationInterface
     */
    private $application;

    /**
     * @var \ArrayObject
     */
    private $paramCollection = null;

    /**
     * ApplicationEvent constructor.
     * @param ApplicationInterface $application
     */
    public function __construct(ApplicationInterface $application)
    {
        $this->application = $application;
        $this->paramCollection = new ArrayObject();
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
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     * @return ApplicationEvent
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return ApplicationInterface
     */
    public function getApplication()
    {
        return $this->application;
    }

    /**
     * @return \ArrayObject
     */
    public function getParamCollection()
    {
        return $this->paramCollection;
    }
}