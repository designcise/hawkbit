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

/**
 * Created by PhpStorm.
 * User: marco.bunge
 * Date: 03.10.2016
 * Time: 21:54
 */

namespace Turbine\Application;


use League\Event\AbstractEvent;

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
     * @return mixed
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @param mixed $response
     */
    public function setResponse($response)
    {
        $this->response = $response;
    }

    /**
     * @return mixed
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @param mixed $request
     */
    public function setRequest($request)
    {
        $this->request = $request;
    }

    /**
     * @return mixed
     */
    public function getErrorResponse()
    {
        return $this->errorResponse;
    }

    /**
     * @param mixed $errorResponse
     */
    public function setErrorResponse($errorResponse)
    {
        $this->errorResponse = $errorResponse;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

}