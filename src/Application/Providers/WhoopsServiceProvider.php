<?php
/**
 * Created by PhpStorm.
 * User: marco.bunge
 * Date: 02.11.2016
 * Time: 17:22
 */

namespace Hawkbit\Application\Providers;


use Hawkbit\Application;
use Hawkbit\Application\Services\Whoops\ApplicationSystemFacade;
use Hawkbit\ApplicationInterface;
use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;
use Whoops\Handler\Handler;
use Whoops\Handler\JsonResponseHandler;
use Whoops\Handler\PlainTextHandler;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Handler\XmlResponseHandler;
use Whoops\Run;

class WhoopsServiceProvider extends AbstractServiceProvider implements BootableServiceProviderInterface
{



    /**
     * Use the register method to register items with the container via the
     * protected $this->container property or the `getContainer` method
     * from the ContainerAwareTrait.
     *
     * @return void
     */
    public function register()
    {

    }

    /**
     * Method will be invoked on registration of a service provider implementing
     * this interface. Provides ability for eager loading of Service Providers.
     *
     * @return void
     */
    public function boot()
    {
        /** @var Application\AbstractApplication $app */
        $app = $this->getContainer()->get(ApplicationInterface::class);

        $errorHandler = new Run(new ApplicationSystemFacade($app));
        $errorHandler->pushHandler(function() use ($app){
            $class = PrettyPageHandler::class;
            if ($app->isCli() || false === $app->getConfig(ApplicationInterface::KEY_ERROR, ApplicationInterface::DEFAULT_ERROR)) {
                $class = PlainTextHandler::class;
            }

            if($app instanceof Application){
                if ($app->isSoapRequest() || $app->isXmlRequest()) {
                    $class = XmlResponseHandler::class;
                } elseif ($app->isAjaxRequest() || $app->isJsonRequest()) {
                    $class = JsonResponseHandler::class;
                }
            }

            return new $class;
        });

        $errorHandler->pushHandler(function (\Exception $exception) use ($app) {

            // log all errors
            $app->getLogger()->error($exception->getMessage());

            $applicationEvent = $app->getApplicationEvent();
            $applicationEvent->setName(ApplicationInterface::EVENT_SYSTEM_ERROR);
            $app->emit($applicationEvent, $exception);

            return Handler::DONE;
        });

        $service = new Application\Services\WhoopsService($errorHandler, $app);

        $this->getContainer()->share(Application\Services\WhoopsService::class, $service);
    }
}