<?php
/**
 * Created by PhpStorm.
 * User: marco.bunge
 * Date: 03.10.2016
 * Time: 13:12
 */

namespace Turbine\Application;


use League\Tactician\Middleware;
use Turbine\Application;

/**
 * Configure service providers from
 * Class ConfigMiddleware
 * @package Turbine\Application
 */
class ServiceProvidersFromConfigMiddleware implements Middleware
{
    protected $configKey = 'providers';

    /**
     * @param Application $command
     * @param callable $next
     *
     * @return mixed
     */
    public function execute($command, callable $next)
    {
        if(!$command instanceof Application){
            return $next($command);
        }

        $configKey = $this->configKey;
        if(!$command->hasConfig($configKey)){
            return $next($command);
        }

        $providers = $command->getConfig($configKey);

        if(is_array($providers)){
            foreach ($providers as $provider){
                $command->getContainer()->addServiceProvider($provider);
            }
        }

        return $next($command);

    }
}