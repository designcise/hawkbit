<?php
/**
 *
 * (c) Marco Bunge <marco_bunge@web.de>
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 *
 * Date: 07.03.2016
 * Time: 17:06
 *
 */

namespace TurbineTests\Stratigility;


use Psr\Http\Message\ResponseInterface;
use Turbine\Application;
use Turbine\Stratigility\MiddlewareAdapter;

class MiddlewareAdapterTest extends \PHPUnit_Framework_TestCase
{


    public function testFunctionPiping()
    {
        $app = new Application();
        $app->get('/', function($request, ResponseInterface $response){
            $response->getBody()->write('Hello');
        });
        $mwApp = new MiddlewareAdapter(new Application());

        $mwApp->pipe('/', function($request, $response, $next){

        });

    }
}
