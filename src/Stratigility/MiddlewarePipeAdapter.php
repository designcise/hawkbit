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
     * @var bool
     */
    private $catchErrors = true;

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
     * @return boolean
     */
    public function canCatchErrors()
    {
        return $this->catchErrors;
    }

    /**
     * @param boolean $catchErrors
     * @return MiddlewarePipeAdapter
     */
    public function setCatchErrors($catchErrors)
    {
        $this->catchErrors = $catchErrors;

        return $this;
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
        $response = parent::__invoke($request, $response, $out);
        $application = $this->getApplication();
        try{
            $response = $application->handleRequest($request, $response);
        }catch(\Exception $exception){
            $response = $application->handleError($request, $exception, $this->canCatchErrors());
        }

        return $response;
    }

}