<?php
/**
 * Created by PhpStorm.
 * User: marco.bunge
 * Date: 02.02.2017
 * Time: 15:30
 */

namespace Hawkbit\Tests\TestAsset;


use Hawkbit\Application\ApplicationInterface;
use Hawkbit\Console;

class TestableCommand
{
    /**
     * @var ApplicationInterface|Console
     */
    private $application;

    /**
     * TestableCommand constructor.
     * @param ApplicationInterface $application
     */
    public function __construct(ApplicationInterface $application)
    {
        $this->application = $application;
    }

    public function handle(){

        // register feedback in container and transport to bootstrap (test case)
        $instance = $this;
        $this->application['testFeedback'] = function() use ($instance) {
            return $instance;
        };
    }

}