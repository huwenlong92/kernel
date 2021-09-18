<?php

namespace Larkit\Kernel\Contracts;

/**
 * Interface MediaInterface.
 *
 */
interface MediaInterface extends MessageInterface
{
    public function getMediaId(): string;
}
