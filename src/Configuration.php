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

/**
 * Dot chaining support for nested configuratio
 *
 * @see \ZeroXF10\Turbine\Configuration::internalGet()
 * @see \ZeroXF10\Turbine\Configuration::internalSet()
 * @see \ZeroXF10\Turbine\Configuration::internalUnset()
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

namespace ZeroXF10\Turbine;


use Zend\Config\Config;

/**
 * Supports dot chaining for nested configuration inspired by M1/vars
 *
 * Class Configuration
 * @package ZeroXF10\Turbine
 */
final class Configuration extends Config
{

    /**
     * The internal get function for getting values by their key
     *
     * @param array $array  The array to use -- will always be $this->content
     * @param mixed $key    The key to find the value for
     * @param bool  $exists Whether to return null or false dependant on the calling function
     *
     * @return array|bool|null The resource key value
     */
    private function internalGet(array $array, $key, $exists = false)
    {
        if (isset($array[$key])) {
            return (!$exists) ? $array[$key] : true;
        }
        $parts = explode('.', $key);
        $partsSuperSet = $parts;
        foreach ($parts as $part) {
            if (!is_array($array) || !isset($array[$part])) {
                return (!$exists) ? null : false;
            }
            unset($partsSuperSet[$part]);
            $subSetPart = implode('.', $partsSuperSet);
            if(0 < count($partsSuperSet) && isset($array[$subSetPart])){
                return (!$exists) ? $array : true;
            }
            $array = $array[$part];
        }
        return (!$exists) ? $array : true;
    }

    /**
     * Object oriented set access for the array
     *
     * @param array $array  The array to use -- will always be based on $this->content but can be used recursively
     * @param mixed $key    The key to set the value for
     * @param mixed $value  The value to set
     *
     * @return array Returns the modified array
     */
    private function internalSet(array &$array, $key, $value)
    {
        if (is_null($key)) {
            return $array = $value;
        }
        $parts = explode('.', $key);
        $partsSuperSet = $parts;
        while (count($parts) > 0) {
            $part = array_shift($parts);
            if (! isset($array[$part]) || !is_array($array[$part])) {
                $array[$part] = $value;
                break;
            }
            unset($partsSuperSet[$part]);
            $subSetPart = implode('.', $partsSuperSet);
            if(0 < count($partsSuperSet) && isset($array[$subSetPart])){
                $array = &$array[$subSetPart];
            }else{
                $array = &$array[$part];
            }
        }
        $array[array_shift($parts)] = (is_array($value) || $value instanceof \ArrayAccess) ? $this->merge(new self($value)) : $array;
        return $array;
    }

    /**
     * Internal unset for the key
     *
     * @param array $array The array to use -- will always be based on $this->content but can be used recursively
     * @param mixed $key The key to unset
     */
    private function internalUnset(array &$array, $key)
    {
        $parts = explode('.', $key);
        $partsSuperSet = $parts;
        while (count($parts) > 0) {
            $part = array_shift($parts);
            unset($partsSuperSet[$part]);
            $subSetPart = implode('.', $partsSuperSet);
            if(0 < count($partsSuperSet) && isset($array[$subSetPart])){
                $array =& $array[$subSetPart];
                break;
            }
            if (isset($array[$part]) && is_array($array[$part])) {
                $array =& $array[$part];
            }
        }
        unset($array[array_shift($parts)]);
    }

    /**
     * @param string $name
     * @param null $default
     * @return array|bool|null
     */
    public function get($name, $default = null)
    {

        return $this->__isset($name) ? $this->internalGet($this->data, $name) : $default;
    }

    /**
     * @param string $name
     * @param mixed $value
     */
    public function __set($name, $value)
    {
        $this->internalSet($this->data, $name, $value);
    }

    /**
     * @param string $name
     * @return array|bool|null
     */
    public function __isset($name)
    {
        return $this->internalGet($this->data, $name, true);
    }

    /**
     * @param string $name
     */
    public function __unset($name)
    {
        $this->internalUnset($this->data, $name);
    }

}