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
    private $name;

    /**
     * Command constructor.
     * @param $name
     * @param $handler
     * @param array $arguments
     */
    public function __construct($name, $handler, array $arguments = null)
    {
        $this->handler = $handler;

        $manager = new Manager();
        if(!is_null($arguments)){
            $manager->add($arguments);
        }
        $this->arguments = $manager;
        $this->name = $name;
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