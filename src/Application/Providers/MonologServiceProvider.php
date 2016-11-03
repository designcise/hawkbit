<?php
/**
 * Created by PhpStorm.
 * User: marco.bunge
 * Date: 03.11.2016
 * Time: 10:16
 */

namespace Hawkbit\Application\Providers;


use League\Container\ServiceProvider\AbstractServiceProvider;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

class MonologServiceProvider extends AbstractServiceProvider
{

    protected $provides = [
        LoggerInterface::class
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
        $this->getContainer()->add(LoggerInterface::class, Logger::class);
    }
}