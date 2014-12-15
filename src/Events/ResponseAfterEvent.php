<?php
/**
 * The Proton Micro Framework
 *
 * @author  Alex Bilbie <hello@alexbilbie.com>
 * @license MIT
 */
namespace Proton\Events;

class ResponseAfterEvent extends ProtonEvent
{
    /**
     * Get the event name.
     *
     * @return string
     */
    public function getName()
    {
        return 'response.after';
    }
}
