<?php


namespace Larkit\Kernel\Events;

use Symfony\Component\HttpFoundation\Response;

/**
 * Class ServerGuardResponseCreated.
 *
 */
class ServerGuardResponseCreated
{
    /**
     * @var \Symfony\Component\HttpFoundation\Response
     */
    public $response;

    public function __construct(Response $response)
    {
        $this->response = $response;
    }
}
