<?php
/**
 * Created by PhpStorm.
 * User: marco.bunge
 * Date: 03.10.2016
 * Time: 15:50
 */

namespace Turbine\Tests\TestAsset;


use League\Container\ServiceProvider\AbstractServiceProvider;

class TestServiceProvider extends AbstractServiceProvider
{

    protected $provides= [
        'TestService'
    ];

    /**
     * Use the register method to register items with the container via the
     * protected $this->container property or the `getContainer` method
     * from the ContainerAwareTrait.
     *
     * @return void
     */
    public function register()
    {
        $this->getContainer()->add('TestService', new \stdClass);
    }
}