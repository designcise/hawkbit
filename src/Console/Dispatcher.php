<?php
/**
 * Created by PhpStorm.
 * User: marco.bunge
 * Date: 02.02.2017
 * Time: 00:52
 */

namespace Hawkbit\Console;

use Interop\Container\ContainerInterface;
use League\Container\Container;
use League\Container\ReflectionContainer;

class Dispatcher
{
    /**
     * @var array|Command[]
     */
    private $commands;
    /**
     * @var Container
     */
    private $container;

    /**
     * Dispatcher constructor.
     * @param Command[] $commands
     * @param Container|ContainerInterface $container
     */
    public function __construct(array $commands, Container $container)
    {
        $this->commands = $commands;
        $dispatcherContainer = clone $container;
        $dispatcherContainer->delegate(new ReflectionContainer());
        $this->container = $dispatcherContainer;
    }

    /**
     * @param $command
     * @return bool
     */
    private function hasCommand($command)
    {
        return isset($this->commands[$command]);
    }

    /**
     * Dispatch handler for given command
     *
     * @param $argv
     */
    public function dispatch($argv){
        $name = reset($argv);

        if(!$this->hasCommand($name)){
            throw new \InvalidArgumentException('Command not found');
        }

        // load command
        $command = $this->commands[$name];

        // parse arguments
        $arguments = $command->getArguments();
        $arguments->parse($argv);

        // execute command
        $handler = $command->getHandler();
        if(is_array($handler)){
            $class = $handler[0];
//            if($this->container->has($class)){
//                $this->container->add($class);
//            }
            $obj = $this->container->get($class);
            $handler[0] = $obj;
        }
        call_user_func_array($handler, [$arguments]);
    }

}