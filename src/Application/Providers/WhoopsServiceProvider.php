<?php
/**
 * Created by PhpStorm.
 * User: marco.bunge
 * Date: 02.11.2016
 * Time: 17:22
 */

namespace Hawkbit\Application\Providers;


use Hawkbit\Application\Services\Whoops\HandlerService;
use Hawkbit\Application;
use Hawkbit\Application\Services\Whoops\ApplicationSystemFacade;
use Hawkbit\Application\ApplicationInterface;
use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;
use Whoops\Handler\Handler;
use Whoops\Handler\HandlerInterface;
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
        $handlerService = new HandlerService($app);

        $errorHandler->pushHandler([$handlerService, 'recognizeErrorResponseHandler']);
        $errorHandler->pushHandler([$handlerService, 'notifySystemWithError']);
        $errorHandler->register();

        $service = new Application\Services\WhoopsService($errorHandler, $app);
        $this->getContainer()->share(Application\Services\WhoopsService::class, $service);
    }
}