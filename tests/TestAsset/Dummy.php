<?php
/**
 * Created by PhpStorm.
 * User: marco.bunge
 * Date: 02.01.2017
 * Time: 09:02
 */

namespace Hawkbit\Tests\TestAsset;


class sDummy
{
    private $value;

    /**
     * Dummy constructor.
     * @param $value
     */
    public function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

}