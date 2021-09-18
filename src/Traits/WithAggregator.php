<?php


namespace Larkit\Kernel\Traits;

use Larkit\Kernel\BaseClient;
use Larkit\Kernel\Delegation\DelegationTo;
use Larkit\Kernel\LarkitConfig;

trait WithAggregator
{
    /**
     * Aggregate.
     */
    protected function aggregate()
    {
        foreach (LarkitConfig::config() as $key => $value) {
            $this['config']->set($key, $value);
        }
    }

    /**
     * @return bool
     */
    public function shouldDelegate($id)
    {
        return $this['config']->get('delegation.enabled')
            && $this->offsetGet($id) instanceof BaseClient;
    }

    /**
     * @return $this
     */
    public function shouldntDelegate()
    {
        $this['config']->set('delegation.enabled', false);

        return $this;
    }

    /**
     * @param $id
     * @return DelegationTo
     */
    public function delegateTo($id)
    {
        return new DelegationTo($this, $id);
    }
}