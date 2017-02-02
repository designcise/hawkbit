<?php
/**
 * Created by PhpStorm.
 * User: marco.bunge
 * Date: 02.02.2017
 * Time: 15:25
 */

namespace Hawkbit\Application\Init;


use Hawkbit\Application;
use Hawkbit\Console;

trait InitConfigurationTrait
{
    /**
     * @param $configuration
     */
    protected function initConfiguration($configuration)
    {
        /** @var $this Application|Console */
        if (is_bool($configuration)) {
            $this->setConfig($this::KEY_ERROR, $configuration);
        } elseif (
            is_array($configuration) ||
            ($configuration instanceof \ArrayAccess ||
                $configuration instanceof \Traversable)
        ) {
            $this->setConfig($configuration);
        }
    }
}