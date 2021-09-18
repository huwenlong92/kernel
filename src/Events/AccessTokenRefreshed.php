<?php

namespace Larkit\Kernel\Events;

use Larkit\Kernel\AccessToken;

/**
 * Class AccessTokenRefreshed.
 *
 */
class AccessTokenRefreshed
{
    /**
     * @var \Larkit\Kernel\AccessToken
     */
    public $accessToken;

    public function __construct(AccessToken $accessToken)
    {
        $this->accessToken = $accessToken;
    }
}
