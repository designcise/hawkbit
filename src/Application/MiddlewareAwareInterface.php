<?php
/**
 * Created by PhpStorm.
 * User: marco.bunge
 * Date: 05.02.2017
 * Time: 12:19
 */

namespace Hawkbit\Application;


interface MiddlewareAwareInterface
{

    /**
     * Add a middleware
     *
     * @param callable $middleware
     */
    public function addMiddleware(callable $middleware);

    /**
     * @return callable[]
     */
    public function getMiddlewares();
}