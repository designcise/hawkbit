<?php
/**
 * Created by PhpStorm.
 * User: marco.bunge
 * Date: 03.11.2016
 * Time: 08:37
 */

namespace Hawkbit\Application\Services\Whoops;


use Hawkbit\Application;
use Hawkbit\ApplicationInterface;
use League\Event\EmitterTrait;
use Whoops\Util\SystemFacade;

class ApplicationSystemFacade extends SystemFacade
{
    private $errorHandler;
    private $exceptionHandler;
    /**
     * @var Application
     */
    private $application;

    /**
     * ApplicationSystemFacade constructor.
     * @param Application\AbstractApplication $application
     */
    public function __construct(Application\AbstractApplication $application)
    {
        $this->application = $application;
    }


    /**
     * @param callable $handler
     * @param int|string $types
     * @return EmitterTrait
     */
    public function setErrorHandler(callable $handler, $types = 'use-php-defaults')
    {
        $this->errorHandler = $handler;
        return $this->application->addListener(ApplicationInterface::EVENT_HANDLE_ERROR, $this->errorHandler);
    }

    /**
     * @param callable $handler
     * @return EmitterTrait
     */
    public function setExceptionHandler(callable $handler)
    {
        $this->exceptionHandler = $handler;
        return $this->application->addListener(ApplicationInterface::EVENT_SYSTEM_EXCEPTION, $this->exceptionHandler);
    }

    /**
     *
     */
    public function restoreExceptionHandler()
    {
        $this->application->removeListener(ApplicationInterface::EVENT_HANDLE_ERROR, $this->exceptionHandler);
        $this->exceptionHandler = null;
    }

    /**
     *
     */
    public function restoreErrorHandler()
    {
        $this->application->removeListener(ApplicationInterface::EVENT_SYSTEM_EXCEPTION, $this->exceptionHandler);
        $this->exceptionHandler = null;
    }

    /**
     * @param callable $function
     * @return EmitterTrait
     */
    public function registerShutdownFunction(callable $function)
    {
        return $this->application->addListener(ApplicationInterface::EVENT_SYSTEM_SHUTDOWN, $function);
    }

}