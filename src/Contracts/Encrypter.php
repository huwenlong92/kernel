<?php

declare(strict_types=1);


namespace Larkit\Kernel\Contracts;

interface Encrypter
{
    /**
     * Encrypt the given value.
     *
     * @param string $value
     *
     * @return string
     */
    public function encrypt($value);

    /**
     * Decrypt the given value.
     *
     * @param string $payload
     *
     * @return string
     */
    public function decrypt($payload);
}
