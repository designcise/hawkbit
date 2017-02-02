<?php
/**
 * Created by PhpStorm.
 * User: marco.bunge
 * Date: 02.11.2016
 * Time: 17:27
 */

namespace Hawkbit\Application\Services;


use Hawkbit\Application;
use Hawkbit\Application\ApplicationInterface;
use Whoops\Run;

class WhoopsService
{
    private $service;
    /**
     * @var Run
     */
    private $runner;
    /**
     * @var Application\AbstractApplication
     */
    private $application;

    /**
     * ErrorHandler constructor.
     * @param Run $runner
     * @param Application\AbstractApplication $application
     */
    public function __construct(Run $runner, Application\AbstractApplication $application)
    {
        $this->runner = $runner;
        $this->application = $application;
    }

    /**
     * Determine error message by error configuration
     *
     * @param \Throwable|\Exception $exception
     *
     * @return string
     *
     */
    public function getErrorMessage($exception)
    {
        $application = $this->application;
        $errorHandler = $this->runner;

        // quit if error occured
        $shouldQuit = $application->isError();

        //
        if($application instanceof Application){
            // if request type ist not strict e.g. xml or json consider error config
            if (!$application->isXmlRequest() && !$application->isJsonRequest()) {
                $shouldQuit = $shouldQuit && $application->getConfig(ApplicationInterface::KEY_ERROR, ApplicationInterface::DEFAULT_ERROR);
            }
        }

        $errorHandler->allowQuit($shouldQuit);

        $method = $errorHandler::EXCEPTION_HANDLER;
        ob_start();
        $errorHandler->$method($exception);
        $message = ob_get_clean();

        return $message;
    }

    /**
     * Convert any type into an exception
     *
     * @param $error
     * @return \Exception|string
     */
    public function decorateException($error)
    {

        $application = $this->application;

        if (is_callable($error)) {
            $error = $application->getContainer()->call($error, [$application]);
        }

        if (is_object($error) && !($error instanceof \Exception)) {
            $error = method_exists($error,
                '__toString') ? $error->__toString() : 'Error with object ' . get_class($error);
        }

        if (is_resource($error)) {
            $error = 'Error with resource type ' . get_resource_type($error);
        }

        if (is_array($error)) {
            $error = implode("\n", $error);
        }

        if (!($error instanceof \Exception)) {
            $error = new \Exception(is_scalar($error) ? $error : 'Error with ' . gettype($error));
        }

        return $error;
    }
}