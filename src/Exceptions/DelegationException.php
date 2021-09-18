<?php

declare(strict_types=1);

namespace Larkit\Kernel\Exceptions;

use Exception;

class DelegationException extends Exception
{
    /**
     * @var string
     */
    protected $exception;

    /**
     * @param string $exception
     */
    public function setException($exception)
    {
        $this->exception = $exception;

        return $this;
    }

    /**
     * @return string
     */
    public function getException()
    {
        return $this->exception;
    }
}
