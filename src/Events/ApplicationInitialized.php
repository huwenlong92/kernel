<?php

namespace Larkit\Kernel\Events;

use Larkit\Kernel\ServiceContainer;

/**
 * Class ApplicationInitialized.
 *
 */
class ApplicationInitialized
{
    /**
     * @var \Larkit\Kernel\ServiceContainer
     */
    public $app;

    public function __construct(ServiceContainer $app)
    {
        $this->app = $app;
    }
}
