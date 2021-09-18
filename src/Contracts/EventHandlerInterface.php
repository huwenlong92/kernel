<?php

namespace Larkit\Kernel\Contracts;

/**
 * Interface EventHandlerInterface.
 *
 */
interface EventHandlerInterface
{
    /**
     * @param mixed $payload
     */
    public function handle($payload = null);
}
