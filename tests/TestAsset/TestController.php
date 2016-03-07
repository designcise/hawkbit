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

namespace TurbineTests\TestAsset;


use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class TestController
{
    public function getIndex(ServerRequestInterface $request, ResponseInterface $response, array $args = [])
    {
        return __FUNCTION__;
    }
}