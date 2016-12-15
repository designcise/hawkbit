<?php
/**
 * The Hawkbit Micro Framework. An advanced derivate of Proton Micro Framework
 *
 * @author Marco Bunge <marco_bunge@web.de>
 * @author Alex Bilbie <hello@alexbilbie.com>
 * @copyright Marco Bunge <marco_bunge@web.de>
 *
 * @license MIT
 */

namespace Hawkbit\Application;


use League\Event\AbstractEvent;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Hawkbit\ApplicationInterface;
use Zend\Stdlib\ArrayObject;

class HttpApplicationEvent extends ApplicationEvent
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
     * @return ResponseInterface
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @param ResponseInterface $response
     * @return HttpApplicationEvent
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
     * @return HttpApplicationEvent
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
     * @return HttpApplicationEvent
     */
    public function setErrorResponse($errorResponse)
    {
        $this->errorResponse = $errorResponse;
        return $this;
    }
}