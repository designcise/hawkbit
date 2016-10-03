<?php
/**
 * Created by PhpStorm.
 * User: marco.bunge
 * Date: 03.10.2016
 * Time: 14:19
 */

namespace Turbine\Application;


use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface HttpMiddlewareInterface
{

    /**
     * HttpMiddlewareInterface constructor.
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     */
    public function __construct(ServerRequestInterface $request, ResponseInterface $response);

}