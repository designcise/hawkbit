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

namespace Hawkbit\Tests\TestAsset;

use Hawkbit\Application;
use Hawkbit\ApplicationInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @codeCoverageIgnore
 */
class SharedTestController
{
    /**
     * @var
     */
    private $application;

    /**
     * @param mixed $application
     */
    public function __construct(ApplicationInterface $application = null)
    {
        $this->application = $application;
    }

    /**
     * @return Application
     */
    public function getApplication()
    {
        return $this->application;
    }

    public function getIndex(ServerRequestInterface $request, ResponseInterface $response, array $args = [])
    {
        $this->getApplication()->setConfig('customValueFromController', __FUNCTION__);
        return __FUNCTION__;
    }
}