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

namespace Turbine\Application;


use Application\Middleware\Delegate;
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
     * @param array $middlewares
     * @return $this
     */
    public function addMiddleware($middlewares)
    {
        $this->middlewares = $middlewares;
        return $this;
    }

    public function run(ServerRequestInterface $request, ResponseInterface $response, callable $fail = null)
    {
        $middlewares = $this->middlewares;

        if (!is_callable($fail)) {
            $fail = function ($exception, $request, $response, $last) {
                throw $exception;
            };
        }

        $last = function ($request, $response) {
            // no op
        };

        try {
            while ($middleware = array_pop($middlewares)) {
                $last = function ($request, $response) use ($middleware, $last) {
                    return call_user_func_array($middleware, [$request, $response, $last]);
                };
            }
        } catch (\Exception $e) {
            $last = call_user_func_array($fail, [$e, $request, $response, $last]);
        }

        return $last($request, $response);
    }

}