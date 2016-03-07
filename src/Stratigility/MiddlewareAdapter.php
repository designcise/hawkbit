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

class MiddlewareAdapter extends MiddlewarePipe
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
     * @param callable|null $next
     * @return ResponseInterface|void
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next = null){

        $application = $this->getApplication();

        // Passes the request to the container
        $application->getContainer()->add(ServerRequestInterface::class, $request);

        try {

            //process request
            $application->emit('request.received', $request);
            $response = parent::__invoke($request, $application->getRouter()->dispatch(
                $request,
                new HtmlResponse('')
            ), $next);
            $application->emit('response.created', $request, $response);

        } catch (\Exception $e) {

            //process errors
            $response = parent::__invoke($request, $response, $e);
            $application->emit('response.created', $request, $response, $e);
        }

        return $response;
    }

}