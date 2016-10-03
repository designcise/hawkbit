<?php
/**
 * Created by PhpStorm.
 * User: marco.bunge
 * Date: 03.10.2016
 * Time: 15:45
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
