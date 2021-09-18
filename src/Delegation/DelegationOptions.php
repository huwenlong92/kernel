<?php

declare(strict_types=1);

namespace Larkit\Kernel\Delegation;


use Larkit\Kernel\LarkitConfig;

class DelegationOptions
{
    /**
     * @var array
     */
    protected $config = [
        'enabled' => false,
    ];

    /**
     * @return $this
     */
    public function enable()
    {
        $this->config['enabled'] = true;

        return $this;
    }

    /**
     * @return $this
     */
    public function disable()
    {
        $this->config['enabled'] = false;

        return $this;
    }

    /**
     * @param bool $ability
     *
     * @return $this
     */
    public function ability($ability)
    {
        $this->config['enabled'] = (bool)$ability;

        return $this;
    }

    /**
     * @param string $host
     *
     * @return $this
     */
    public function toHost($host)
    {
        $this->config['host'] = $host;

        return $this;
    }

    /**
     * Destructor.
     */
    public function __destruct()
    {
        LarkitConfig::mergeConfig([
            'delegation' => $this->config,
        ]);
    }
}
