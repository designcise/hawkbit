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

/**
 * Dot chaining support for nested configuratio
 *
 * @see \Hawkbit\Configuration::internalGet()
 * @see \Hawkbit\Configuration::internalSet()
 * @see \Hawkbit\Configuration::internalUnset()
 *
 * (c) m1 <hello@milescroxford.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with m1/vars source code.
 *
 * @package     m1/vars
 * @version     1.1.0
 * @author      Miles Croxford <hello@milescroxford.com>
 * @copyright   Copyright (c) Miles Croxford <hello@milescroxford.com>
 * @license     http://github.com/m1/vars/blob/master/LICENSE
 * @link        http://github.com/m1/vars/blob/master/README.MD Documentation
 */

namespace Hawkbit;


use Zend\Config\Config;

/**
 * Supports dot chaining for nested configuration inspired by M1/vars
 *
 * Class Configuration
 * @package Hawkbit
 */
final class Configuration extends Config
{

}