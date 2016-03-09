<?php
/*
*
* (c) Marco Bunge <marco_bunge@web.de>
*
* For the full copyright and license information, please view the LICENSE.txt
* file that was distributed with this source code.
*
* Date: 05.03.2016
* Time: 00:00
*/

namespace Turbine;


use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface ApplicationInterface
{

    /**
     * Handles a Request to convert it to a Response.
     *
     * When $catch is true, the implementation must catch all exceptions
     * and do its best to convert them to a Response instance.
     *
     * @param ServerRequestInterface $request A Request instance
     * @param ResponseInterface $response A response instance
     * @param bool $catch Whether to catch exceptions or not
     *
     * @return ResponseInterface A Response instance
     *
     */
    public function handle(ServerRequestInterface $request, ResponseInterface $response = null, $catch = true);

    /**
     * Handle response / request lifecycle
     *
     * When $callable is a valid callable, callable will executed before emit response
     *
     * @param ServerRequestInterface $request A Request instance
     * @param ResponseInterface $response A response instance
     * @param null|callable $callable Call a handler before response is terminated
     * @return ResponseInterface
     *
     */
    public function run(ServerRequestInterface $request = null, ResponseInterface $response = null, $callable = null);
}