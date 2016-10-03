<?php
/**
 * The Turbine Micro Framework. An advanced derivate of Proton Micro Framework
 *
 * @author Marco Bunge <marco_bunge@web.de>
 * @author Alex Bilbie <hello@alexbilbie.com>
 * @copyright Marco Bunge <marco_bunge@web.de>
 *
 * @license MIT
 */

namespace Turbine\Tests;


use Turbine\Application;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequestFactory;

class ApplicationEventTest extends \PHPUnit_Framework_TestCase
{

    public function testDelegation()
    {
        $app = new Application();

        $event = new Application\ApplicationEvent(Application\ApplicationEvent::EVENT_REQUEST_RECEIVED, $app);
        $event->setRequest(ServerRequestFactory::fromGlobals());

        $this->assertEquals(Application\ApplicationEvent::EVENT_REQUEST_RECEIVED, $event->getName());

        $event = $event->delegate(Application\ApplicationEvent::EVENT_RESPONSE_CREATED, $event);
        $event->setResponse(new Response());

        $this->assertEquals(Application\ApplicationEvent::EVENT_RESPONSE_CREATED, $event->getName());
    }
}
