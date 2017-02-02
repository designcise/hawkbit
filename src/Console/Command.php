<?php
/**
 * Created by PhpStorm.
 * User: marco.bunge
 * Date: 02.02.2017
 * Time: 00:51
 */

namespace Hawkbit\Console;


use League\CLImate\Argument\Manager;

class Command
{
    /**
     * @var callable
     */
    private $handler;
    /**
     * @var Manager
     */
    private $arguments;

    /**
     * Command constructor.
     * @param callable $handler
     * @param array $arguments
     */
    public function __construct($name, callable $handler, array $arguments = null)
    {
        $this->handler = $handler;

        $manager = new Manager();
        if(!is_null($arguments)){
            $manager->add($arguments);
        }
        $this->arguments = $manager;
    }

    /**
     * @return callable
     */
    public function getHandler()
    {
        return $this->handler;
    }

    /**
     * @return Manager
     */
    public function getArguments()
    {
        return $this->arguments;
    }

}