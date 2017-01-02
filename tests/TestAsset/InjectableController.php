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


use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @codeCoverageIgnore
 */
class InjectableController
{
    /**
     * @var SingletonDummy
     */
    private $dummy;

    /**
     * TestInjectableController constructor.
     * @param SingletonDummy $dummy
     */
    public function __construct(SingletonDummy $dummy)
    {
        $this->dummy = $dummy;
    }

    public function getIndex(ServerRequestInterface $request, ResponseInterface $response, array $args = [])
    {
        $response->getBody()->write($this->dummy->getValue());
        return $response;
    }
}