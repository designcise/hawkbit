<?php
/**
 * The Turbine Micro Framework. An advanced derivate of Proton Micro Framework
 *
 * @author Marco Bunge <marco_bunge@web.de>
 * @copyright Marco Bunge <marco_bunge@web.de>
 *
 * @license MIT
 */

namespace Turbine\Tests;


use Turbine\Application;
use Turbine\Application\ServiceProvidersFromConfigMiddleware;
use Turbine\Tests\TestAsset\TestServiceProvider;

class MiddlewareTest extends \PHPUnit_Framework_TestCase
{

    public function testServiceProviderconfiguratorMiddleware()
    {
        $application = new Application([
            'providers' => [
                new TestServiceProvider()
            ]
        ]);
        $application->addMiddleware(new ServiceProvidersFromConfigMiddleware());

        //handle middlewares
        $application->handleMiddlewares($application, $application->getMiddlewares());

        $this->assertTrue($application->getContainer()->has('TestService'));

    }


}
