<?php
/**
 * Created by PhpStorm.
 * User: marco.bunge
 * Date: 03.10.2016
 * Time: 13:56
 */

namespace Turbine\Application;


use League\Tactician\Middleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Turbine\Application;

class ApplicationRunnerMiddleware implements Middleware, HttpMiddlewareInterface
{
    /**
     * @var ServerRequestInterface
     */
    private $request;
    /**
     * @var ResponseInterface
     */
    private $response;

    public function __construct(ServerRequestInterface $request, ResponseInterface $response = null)
    {
        $this->request = $request;
        $this->response = $response;
    }

    /**
     * @param object $command
     * @param callable $next
     *
     * @return mixed
     */
    public function execute($command, callable $next)
    {
        if(!$command instanceof Application){
            return $next($command);
        }

        $result = $next($command);

        $request = $this->request;
        $response = $command->handle($request, $this->response);

        $command->emitResponse($request, $response);

        if ($command->canTerminate()) {
            $command->terminate($request, $response);
        }

        $command->shutdown($response);

        return $result;
    }
}