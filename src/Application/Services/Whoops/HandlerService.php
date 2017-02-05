<?php
/**
 * Created by PhpStorm.
 * User: marco.bunge
 * Date: 05.02.2017
 * Time: 09:52
 */

namespace Application\Services\Whoops;


use Hawkbit\Application;
use Hawkbit\Application\ApplicationInterface;
use Whoops\Handler\Handler;
use Whoops\Handler\HandlerInterface;
use Whoops\Handler\JsonResponseHandler;
use Whoops\Handler\PlainTextHandler;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Handler\XmlResponseHandler;
use Whoops\Run;

class HandlerService
{
    /**
     * @var Application\AbstractApplication
     */
    private $application;


    /**
     * HandlerService constructor.
     * @param Application\AbstractApplication $application
     */
    public function __construct(Application\AbstractApplication $application)
    {
        $this->application = $application;
    }

    /**
     * Recognize response handler for error by content type and environment.
     *
     * Cli is always handled as plaintext!
     *
     * @param $exception
     * @param $inspector
     * @param Run $run
     * @return int|null
     */
    public function recognizeErrorResponseHandler($exception, $inspector, Run $run)
    {
        $app = $this->application;

        $handlerClass = PrettyPageHandler::class;
        if ($app->isCli() || false === $app->getConfig(ApplicationInterface::KEY_ERROR, ApplicationInterface::DEFAULT_ERROR)) {
            $handlerClass = PlainTextHandler::class;
        }

        if ($app instanceof Application) {
            if ($app->isSoapRequest() || $app->isXmlRequest()) {
                $handlerClass = XmlResponseHandler::class;
            } elseif ($app->isAjaxRequest() || $app->isJsonRequest()) {
                $handlerClass = JsonResponseHandler::class;
            }
        }

        /** @var HandlerInterface $handler */
        $handler = new $handlerClass;
        $handler->setException($exception);
        $handler->setInspector($inspector);
        $handler->setRun($run);
        return $handler->handle();
    }

    /**
     * Notify system after error occurs
     *
     * @param \Exception|\Throwable $exception
     * @return int
     */
    public function notifySystemWithError($exception)
    {

        $app = $this->application;
        // log all errors
        $app->getLogger()->error($exception->getMessage());

        $applicationEvent = $app->getApplicationEvent();
        $applicationEvent->setName(ApplicationInterface::EVENT_SYSTEM_ERROR);
        $app->emit($applicationEvent, $exception);

        return Handler::DONE;
    }

}