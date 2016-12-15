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
        $result = null;

        // declare default final middleware
        if (!is_callable($final)) {
            $final = function ($command) {
                return $command;
            };
        }

        // declare default error middleware
        if (!is_callable($fail)) {
            $fail = function ($exception) {
                throw $exception;
            };
        }

        try {
            $result = $this->handle($args, $final);
        } catch (\Exception $exception) {
            $result = $this->handleError($args, $fail, $exception, $result);
        }

        return $result;
    }

    /**
     * Resolve middleware queue
     *
     * @param $middlewares
     * @return \Closure
     */
    public function resolve($middlewares)
    {
        $last = function ($request, $response) {
            // no op
        };
        while ($middleware = array_pop($middlewares)) {
            if (is_object($middleware)) {
                if (method_exists($middleware, '__invoke')) {
                    $middleware = [$middleware, '__invoke'];
                }
            }

            if (!is_callable($middleware)) {
                throw new \InvalidArgumentException('Middle needs to be callable');
            }

            $last = function () use ($middleware, $last) {
                $args = func_get_args();
                $args[] = $last;
                return call_user_func_array($middleware, $args);
            };
        }
        return $last;
    }

    /**
     * @param array $args
     * @return mixed
     */
    public function handle(array $args, callable $final)
    {
        $middlewares = $this->middlewares;
        array_push($middlewares, $final);
        return call_user_func_array($this->resolve($middlewares), $args);
    }

    /**
     * @param array $args
     * @param callable $fail
     * @param \Exception|\Throwable $exception
     * @param $result
     * @return mixed
     */
    public function handleError(array $args, callable $fail, $exception, $result)
    {
        // modify middleware args
        // push exception to top
        array_unshift($args, $exception);

        // push result to end
        $args[] = $result;

        // execute error middleware
        $result = call_user_func_array($fail, $args);
        return $result;
    }

}