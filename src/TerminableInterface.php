<?php
/**
 *
 * (c) Marco Bunge <marco_bunge@web.de>
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 *
 * Date: 04.03.2016
 * Time: 14:37
 *
 */

namespace Turbine;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;


/**
 * PSR-7 port of \Symfony\Component\HttpKernel\TerminableInterface
 *
 * Terminable extends the Kernel request/response cycle with dispatching a post
 * response event after sending the response and before shutting down the kernel.
 *
 * @author Jordi Boggiano <j.boggiano@seld.be>
 * @author Pierre Minnieur <pierre.minnieur@sensiolabs.de>
 * @author Marco Bunge <marco_bunge@web.de>
 */
interface TerminableInterface
{
    /**
     * Terminates a request/response cycle.
     *
     * Should be called after sending the response and before shutting down the kernel.
     *
     * @param ServerRequestInterface    $request  A Request instance
     * @param ResponseInterface         $response A Response instance
     */
    public function terminate(ServerRequestInterface $request, ResponseInterface $response);
}
