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

namespace Hawkbit\Stratigility;


use League\Route\Http\Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Hawkbit\Application;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Stratigility\MiddlewarePipe;

class MiddlewarePipeAdapter extends MiddlewarePipe
{
    /**
     * @var Application
     */
    private $application;

    /**
     * MiddlewareAdapter constructor.
     * @param Application $application
     */
    public function __construct(Application $application)
    {
        parent::__construct();
        $this->application = $application;
    }

    /**
     * @return Application
     */
    public function getApplication()
    {
        return $this->application;
    }

    /**
     * Handle the request
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param callable|null $out
     * @return ResponseInterface|void
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $out = null){
        $application = $this->getApplication();

        // handling errors by application with
        // custom $finalHandler if $out is null
        $finalHandler = $out ? $out : function (ServerRequestInterface $request, ResponseInterface $response, $err = null) use($application, $out){
            if($err){
                return $application->handleError($err, $request, $response);
            }
            return $response;
        };
        $this->pipe(function(ServerRequestInterface $request, ResponseInterface $response, $next = null) use ($application){
            return $application->handle($request, $response);
        });
        return parent::__invoke($request, $response, $finalHandler);
    }

}
