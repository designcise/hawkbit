<?php
/**
 * The Turbine Micro Framework. An advanced derivate of Proton Micro Framework
 *
 * @author Marco Bunge <marco_bunge@web.de>
 * @copyright Marco Bunge <marco_bunge@web.de>
 *
 * @license MIT
 */

namespace Turbine\Tests\TestAsset;


use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @codeCoverageIgnore
 */
class TestController
{
    public function getIndex(ServerRequestInterface $request, ResponseInterface $response, array $args = [])
    {
        return __FUNCTION__;
    }
}