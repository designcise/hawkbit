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
        $hasConfig = $command->hasConfig($configKey);
        if(!$hasConfig){
            return $next($command);
        }

        $providers = $command->getConfig($configKey);

        if(is_array($providers) || $providers instanceof \Traversable){
            foreach ($providers as $provider){
                $command->getContainer()->addServiceProvider($provider);
            }
        }

        return $next($command);

    }
}