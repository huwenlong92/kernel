<?php

namespace Larkit\Kernel\Contracts;

use Psr\Http\Message\RequestInterface;

/**
 * Interface AuthorizerAccessToken.
 */
interface AccessTokenInterface
{
    public function getToken(): array;

    /**
     * @return \Larkit\Kernel\Contracts\AccessTokenInterface
     */
    public function refresh(): self;

    public function applyToRequest(RequestInterface $request, array $requestOptions = []): RequestInterface;
}
