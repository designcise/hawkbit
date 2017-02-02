<?php
/**
 * The Hawkbit Micro Framework. An advanced derivate of Proton Micro Framework
 *
 * @author Marco Bunge <marco_bunge@web.de>
 * @author Alex Bilbie <hello@alexbilbie.com>
 * @copyright Marco Bunge <marco_bunge@web.de>
 *
 * @license MIT
 */

namespace Hawkbit\Application;

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
    const EVENT_HANDLE_ERROR = 'handle.error';

    /**
     * This event is always fired when an error occurs.
     */
    const EVENT_SYSTEM_ERROR = 'system.error';

    /**
     * This event is fired before completing application lifecycle.
     */
    const EVENT_LIFECYCLE_COMPLETE = 'lifecycle.complete';

    /**
     * This event is fired on each shutdown.
     */
    const EVENT_SYSTEM_SHUTDOWN = 'system.shutdown';

    /**
     * This event is fired when throw an exception.
     */
    const EVENT_SYSTEM_EXCEPTION = 'system.exception';

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
}
