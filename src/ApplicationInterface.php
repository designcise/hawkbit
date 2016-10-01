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
     * This event is fired when a request is received but before it has been processed by the router.
     */
    const EVENT_REQUEST_RECEIVED = 'request.received';

    /**
     * This event is fired when a response has been created but before it has been output.
     */
    const EVENT_RESPONSE_CREATED = 'response.created';

    /**
     * This event is fired when a response has been output.
     */
    const EVENT_RESPONSE_SENT = 'response.sent';

    /**
     * This event is fired only when an error occurs while handling request/response lifecycle.
     * This event is fired after `runtime.error`
     */
    const EVENT_LIFECYCLE_ERROR = 'lifecycle.error';

    /**
     * This event is always fired when an error occurs.
     */
    const EVENT_RUNTIME_ERROR = 'runtime.error';

    /**
     * This event is fired before completing application lifecycle.
     */
    const EVENT_LIFECYCLE_COMPLETE = 'lifecycle.complete';

    /**
     * This event is fired on each shutdown.
     */
    const EVENT_SHUTDOWN = 'shutdown';

    const KEY_ERROR_CATCH = 'error.catch';
    const KEY_ERROR = 'error';

    /**
     * Show or hide errors
     */
    const DEFAULT_ERROR = false;

    /**
     * catch or throw errors
     */
    const DEFAULT_ERROR_CATCH = true;

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
     *
     * @return ResponseInterface
     *
     */
    public function run(ServerRequestInterface $request = null, ResponseInterface $response = null);
}
