<?php
/**
 * The Proton Micro Framework
 *
 * @author  Alex Bilbie <hello@alexbilbie.com>
 * @license MIT
 */
namespace Proton\Events;

class ResponseBeforeEvent extends ProtonEvent
{
    /**
     * Get the event name.
     *
     * @return string
     */
    public function getName()
    {
        return 'response.before';
    }
}
