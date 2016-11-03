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

namespace Hawkbit\Application;


use League\Event\AbstractEvent;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Hawkbit\ApplicationInterface;
use Zend\Stdlib\ArrayObject;

class ApplicationEvent extends AbstractEvent
{
    /**
     * @var
     */
    private $name;
    /**
     * @var ApplicationInterface
     */
    private $application;

    /**
     * @var \ArrayObject
     */
    private $paramCollection = null;

    /**
     * ApplicationEvent constructor.
     * @param ApplicationInterface $application
     */
    public function __construct(ApplicationInterface $application)
    {
        $this->application = $application;
        $this->paramCollection = new ArrayObject();
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return ApplicationInterface
     */
    public function getApplication()
    {
        return $this->application;
    }

    /**
     * @return \ArrayObject
     */
    public function getParamCollection()
    {
        return $this->paramCollection;
    }
}