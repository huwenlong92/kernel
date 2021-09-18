<?php


namespace Larkit\Kernel;


use Larkit\Kernel\Delegation\DelegationOptions;

class LarkitConfig
{

    /**
     * @var array
     */
    protected static $config = [];

    /**
     * Encryption key.
     *
     * @var string
     */
    protected static $encryptionKey;

    /**
     * @param array $config
     */
    public static function mergeConfig(array $config)
    {
        static::$config = array_merge(static::$config, $config);
    }

    /**
     * @return array
     */
    public static function config()
    {
        return static::$config;
    }

    /**
     * Set encryption key.
     *
     * @param string $key
     *
     * @return static
     */
    public static function setEncryptionKey(string $key)
    {
        static::$encryptionKey = $key;

        return new static();
    }

    /**
     * Get encryption key.
     *
     * @return string
     */
    public static function getEncryptionKey(): string
    {
        return static::$encryptionKey;
    }

    /**
     * @return DelegationOptions
     */
    public static function withDelegation()
    {
        return new DelegationOptions();
    }

}