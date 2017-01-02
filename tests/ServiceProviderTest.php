<?php
/**
 * Created by PhpStorm.
 * User: marco.bunge
 * Date: 02.01.2017
 * Time: 09:01
 */

namespace Hawkbit\Tests;



use Hawkbit\Application;
use Hawkbit\Tests\TestAsset\SingletonDummy;
use Hawkbit\Tests\TestAsset\InjectableController;
use Hawkbit\Tests\TestAsset\TestServiceProvider;
use Zend\Diactoros\ServerRequestFactory;

class ServiceProviderTest extends \PHPUnit_Framework_TestCase
{


    public function testGetSingleton()
    {
        $app = new Application();
        $app->register(TestServiceProvider::class);

        /** @var SingletonDummy $dummy */
        $dummy = $app[SingletonDummy::class];

        $this->assertInstanceOf(SingletonDummy::class, $dummy);
        $this->assertEquals('singleton', $dummy->getValue());
    }

    public function testGetInjectedSingleton()
    {
        $app = new Application([
            Application::KEY_ERROR => true,
            Application::KEY_ERROR_CATCH => false,
        ]);

        $app->register(TestServiceProvider::class);

        $app->get('/', [InjectableController::class, 'getIndex']);

        $response = $app->handle(ServerRequestFactory::fromGlobals());

        $this->assertEquals('singleton', $response->getBody()->__toString());


    }
}
