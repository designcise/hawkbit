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
 * Date: 04.10.2016
 * Time: 00:02
 */

namespace Hawkbit\Application;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class MiddlewareRunner
{

    /**
     * @var array
     */
    private $middlewares;

    /**
     * MiddlewareRunner constructor.
     * @param array $middlewares
     */
    public function __construct(array $middlewares)
    {
        $this->middlewares = $middlewares;
    }

    /**
     * @param $middleware
     * @return $this
     */
    public function addMiddleware($middleware)
    {
        $this->middlewares[] = $middleware;
        return $this;
    }

    /**
     * Execute middlewares
     *
     * @param callable $final
     * @param callable|null $fail
     * @param array $args Arguments for middleware
     * @return mixed
     */
    public function run(array $args = [], callable $final = null, callable $fail = null)
    {
        // declare default final middleware
        if(!is_callable($final)){
            $final = function($request, $response){
                return $response;
            };
        }

        // declare default error middleware
        if (!is_callable($fail)) {
            $fail = function ($exception, $request, $response, $last) {
                throw $exception;
            };
        }

        $last = function ($request, $response) {
            // no op
        };

        $middlewares = $this->middlewares;
        array_push($middlewares, $final);
        $result = null;

        try {
            while ($middleware = array_pop($middlewares)) {
                if(is_object($middleware)){
                    if(method_exists($middleware, '__invoke')){
                        $middleware = [$middleware, '__invoke'];
                    }
                }

                if(!is_callable($middleware)){
                    throw new \InvalidArgumentException('Middle needs to be callable');
                }

                $last = function () use ($middleware, $last) {
                    $args = func_get_args();
                    $args[] = $last;
                    return call_user_func_array($middleware, $args);
                };
            }

            $result = call_user_func_array($last, $args);
        } catch (\Exception $e) {
            // modify middleware args
            // push exception to top
            array_unshift($args, $e);

            // push result to end
            $args[] = $result;

            // execute error middleware
            $result = call_user_func_array($fail, $args);
        }

        return $result;
    }

}