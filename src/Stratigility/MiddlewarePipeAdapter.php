<?php
/**
 *
 * (c) Marco Bunge <marco_bunge@web.de>
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 *
 * Date: 07.03.2016
 * Time: 16:32
 *
 */

namespace Turbine\Stratigility;


use League\Route\Http\Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Turbine\Application;
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
            $application->handle($request, $response);
        });
        return parent::__invoke($request, $response, $finalHandler);
    }

}