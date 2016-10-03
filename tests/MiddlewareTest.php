<?php
/**
 * Created by PhpStorm.
 * User: marco.bunge
 * Date: 03.10.2016
 * Time: 13:09
 */

namespace Turbine\Tests;


use League\Tactician\CommandBus;
use Turbine\Application;
use Turbine\Application\ServiceProvidersFromConfigMiddleware;
use Turbine\Application\RequestResponseMiddleware;

class MiddlewareTest extends \PHPUnit_Framework_TestCase
{

    public function testServiceProviderconfiguratorMiddleware()
    {
        $application = new Application();
        $application->addMiddleware(new ServiceProvidersFromConfigMiddleware());

        //handle middlewares
        $application->handleMiddlewares($application, $application->getMiddlewares());

    }


}
