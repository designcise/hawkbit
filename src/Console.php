<?php
/**
 * Created by PhpStorm.
 * User: marco.bunge
 * Date: 01.02.2017
 * Time: 21:39
 */

namespace Hawkbit;

use Hawkbit\Application\Init\InitHaltHookTrait;
use Hawkbit\Console\ConsoleEvent;
use Hawkbit\Application\AbstractApplication;
use Hawkbit\Application\Init\InitConfigurationTrait;
use Hawkbit\Application\Providers\MonologServiceProvider;
use Hawkbit\Application\Providers\WhoopsServiceProvider;
use Hawkbit\Console\Command;
use Hawkbit\Console\Dispatcher;
use League\Container\ServiceProvider\ServiceProviderInterface;

/**
 * Class Console
 * @package Hawkbit
 *
 * @todo add event handling equals to application
 */
final class Console extends AbstractApplication
{

    use InitConfigurationTrait;
    use InitHaltHookTrait;

    /**
     * A list of commands
     *
     * @var Command[]
     */
    private $commands;

    /**
     * Represents event class for this application
     *
     * @var string
     */
    protected $applicationEventClass = ConsoleEvent::class;

    /**
     * New Application.
     *
     * @param bool|array $configuration Enable debug mode
     * @param ServiceProviderInterface[] $defaultProviders
     */
    public function __construct($configuration = [], array $defaultProviders = [
        MonologServiceProvider::class,
        WhoopsServiceProvider::class
    ])
    {
        $this->init($configuration);

        foreach ($defaultProviders as $provider) {
            $this->getContainer()->addServiceProvider($provider);
        }
    }

    /**
     * Shutdown application lifecycle
     *
     * @return void
     */
    public function shutdown()
    {
        // dispatch shutdown event
        $applicationEvent = $this->getApplicationEvent();
        $applicationEvent->setName(self::EVENT_SYSTEM_SHUTDOWN);
        $this->emit($applicationEvent);

        exit((int)$this->isError());
    }

    /**
     * Initialize Application
     *
     * @param array $configuration
     * @return void
     */
    public function init($configuration = [])
    {
        $this->initConfiguration($configuration);
        $this->initHaltHooks();
    }

    /**
     * Map first argument to callback
     *
     *  - callable
     *  - [class, method], including __invoke
     *
     * Argument matching full integrated from climate http://climate.thephpleague.com/arguments/
     *
     * @param $name
     * @param $callback
     * @param array $arguments
     * @return Command
     */
    public function map($name, $callback, array $arguments = [])
    {
        $command = new Command($name, $callback, $arguments);
        $this->commands[$name] = $command;
        return $command;
    }

    /**
     * Dispatch command from given args
     *
     * @param array $args
     */
    public function handle(array $args = [])
    {

        // remove source file name from argv
        $source = array_shift($args);

        /** @var ConsoleEvent $applicationEvent */
        $applicationEvent = $this->getApplicationEvent();
        $applicationEvent->setName(self::EVENT_REQUEST_RECEIVED);
        $applicationEvent->setSourceFile($source);
        $applicationEvent->setArguments($args);
        $this->emit($applicationEvent);

        // init dispatcher
        $dispatcher = new Dispatcher($this->commands, $this->container);

        // dispatch command with args from cli
        $dispatcher->dispatch($applicationEvent->getArguments());
    }

    /**
     * execute console lifecycle
     *
     * @param array|null $argv
     */
    public function run(array $argv = null)
    {
        // If no $argv is provided then use the global PHP defined $argv.
        if (is_null($argv)) {
            global $argv;
        }

        // handle call
        $this->handle($argv);

        // exit cli with code 0 or even 1 for error
        $this->shutdown();
    }

    /**
     * @param $command
     * @return bool
     */
    public function hasCommand($command)
    {
        return isset($this->commands[$command]);
    }
}