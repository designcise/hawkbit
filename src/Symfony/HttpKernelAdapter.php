<?php
/**
 *
 * (c) Marco Bunge <marco_bunge@web.de>
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 *
 * Date: 04.03.2016
 * Time: 16:09
 *
 */

namespace Turbine\Symfony;



use Turbine\Application;
use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\TerminableInterface;

class HttpKernelAdapter implements HttpKernelInterface, TerminableInterface
{

    /**
     * @var Application
     */
    private $application;

    /**
     * @var DiactorosFactory
     */
    private $diactorosFactory;

    /**
     * @var HttpFoundationFactory
     */
    private $httpFoundationFactory;

    public function __construct(Application $application){

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
     * Handles a Request to convert it to a Response.
     *
     * Bridging between PSR-7 and Symfony HTTP
     *
     * When $catch is true, the implementation must catch all exceptions
     * and do its best to convert them to a Response instance.
     *
     * @param Request $request A Request instance
     * @param int $type The type of the request
     *                         (one of HttpKernelInterface::MASTER_REQUEST or HttpKernelInterface::SUB_REQUEST)
     * @param bool $catch Whether to catch exceptions or not
     *
     * @return Response A Response instance
     *
     * @throws \Exception When an Exception occurs during processing
     */
    public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = true)
    {
        $response = $this->getApplication()->handle($this->getDiactorosFactory()->createRequest($request), $type, $catch);
        return $this->getHttpFoundationFactory()->createResponse($response);
    }

    /**
     * Terminates a request/response cycle.
     *
     * Should be called after sending the response and before shutting down the kernel.
     *
     * @param Request $request A Request instance
     * @param Response $response A Response instance
     */
    public function terminate(Request $request, Response $response)
    {
        $diactorosFactory = $this->getDiactorosFactory();
        ;
        $this->getApplication()->terminate($diactorosFactory->createRequest($this->recomposeSymfonyRequest($request)), $diactorosFactory->createResponse($response));
    }

    /**
     * Convert from symfony http foundation to PSR-7
     *
     * @return DiactorosFactory
     */
    public function getDiactorosFactory()
    {
        if($this->diactorosFactory === null){
            $this->diactorosFactory = new DiactorosFactory();
        }
        return $this->diactorosFactory;
    }

    /**
     * Convert from PSR-7 to symfony http foundation
     *
     * @return HttpFoundationFactory
     */
    public function getHttpFoundationFactory()
    {
        if($this->httpFoundationFactory === null){
            $this->httpFoundationFactory = new HttpFoundationFactory();
        }
        return $this->httpFoundationFactory;
    }

    /**
     * Avoid exception throw when request conetnt is null
     *
     * @param Request $request
     * @return Request
     */
    private function recomposeSymfonyRequest(Request $request)
    {
        try{
            try {
                $content = $request->getContent(true);
            } catch (\LogicException $e) {
                $content = $request->getContent();
            }
        }catch(\LogicException $e){
            $content = file_get_contents('php://input');
        }
        return $request::create(
            $request->getUri(),
            $request->getMethod(),
            $request->getMethod() === 'PATCH' ? $request->request->all() : $request->query->all(),
            $request->cookies->all(),
            $request->files->all(),
            $request->server->all(),
            $content
        );
    }
}