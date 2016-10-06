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

namespace ZeroXF10\Turbine\Application;

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
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param callable $final
     * @param callable|null $fail
     * @return mixed
     */
    public function run(ServerRequestInterface $request, ResponseInterface $response, callable $final = null, callable $fail = null)
    {
        $middlewares = $this->middlewares;

        if(!is_callable($final)){
            $final = function($request, $response){
                return $response;
            };
        }

        if (!is_callable($fail)) {
            $fail = function ($exception, $request, $response, $last) {
                throw $exception;
            };
        }

        array_push($middlewares, $final);

        $last = function ($request, $response) {
            // no op
        };

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

                $last = function ($request, $response) use ($middleware, $last) {
                    return call_user_func_array($middleware, [$request, $response, $last]);
                };
            }

            $result = $last($request, $response);
        } catch (\Exception $e) {
            $result = call_user_func_array($fail, [$e, $request, $response, $result]);
        }

        return $result;
    }

}