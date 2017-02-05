<?php
/**
 * Created by PhpStorm.
 * User: marco.bunge
 * Date: 05.02.2017
 * Time: 11:43
 */

namespace Application\Init;


use Hawkbit\Application;
use Hawkbit\Console;

trait InitHaltHookTrait
{

    /**
     * Init all hooks which will be execute on system shutdown or error
     */
    protected function initHaltHooks()
    {

        // error handler
        set_error_handler(function ($level, $message, $file = null, $line = null) {
            /** @var $this Application|Console */
            $event = $this->getApplicationEvent();
            $this->emit($event->setName($this::EVENT_HANDLE_ERROR), $level, $message, $file, $line);
        });

        // exception handler
        set_exception_handler(function ($exception) {
            /** @var $this Application|Console */
            /** @var \Exception|\Throwable $exception */
            // Convert throwable to exception for backwards compatibility
            if (!($exception instanceof \Exception)) {
                $throwable = $exception;
                $exception = new \ErrorException(
                    $throwable->getMessage(),
                    $throwable->getCode(),
                    E_ERROR,
                    $throwable->getFile(),
                    $throwable->getLine()
                );
            }

            $event = $this->getApplicationEvent();
            $this->emit($event->setName($this::EVENT_SYSTEM_EXCEPTION), $exception);
        });

        if (method_exists($this, 'shutdown')) {
            // shutdown function
            register_shutdown_function([$this, 'shutdown']);
        }
    }
}