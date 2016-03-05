<?php
/*
*
* (c) Marco Bunge <marco_bunge@web.de>
*
* For the full copyright and license information, please view the LICENSE.txt
* file that was distributed with this source code.
*
* Date: 04.03.2016
* Time: 23:26
*/

namespace ElectronTests\TestAsset;


use Electron\Application;
use Electron\ApplicationInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

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