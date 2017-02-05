<?php
/**
 * Created by PhpStorm.
 * User: marco.bunge
 * Date: 05.02.2017
 * Time: 11:47
 */

namespace Hawkbit\Console;


use Hawkbit\Application\ApplicationEvent;

class ConsoleEvent extends ApplicationEvent
{

    /**
     * @var array
     */
    public $arguments;

    /**
     * @var string
     */
    public $sourceFile;

    /**
     * @return array
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * @param array $arguments
     * @return ConsoleEvent
     */
    public function setArguments($arguments)
    {
        $this->arguments = $arguments;
        return $this;
    }

    /**
     * @return string
     */
    public function getSourceFile()
    {
        return $this->sourceFile;
    }

    /**
     * @param string $sourceFile
     * @return ConsoleEvent
     */
    public function setSourceFile($sourceFile)
    {
        $this->sourceFile = $sourceFile;
        return $this;
    }
    
}