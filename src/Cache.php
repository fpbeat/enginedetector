<?php

namespace EngineDetector;

use EngineDetector\Utils\Arr;
use phpFastCache\Helper\Psr16Adapter;

class Cache {

    const SECONDS_IN_DAY = 86400;
    const DEFAULT_LIFETIME = 30;

    /**
     * @var array $instances
     */
    private static $instances = [];

    /**
     * @var array $config
     */
    private $config = [];

    /**
     * @param array $config
     *
     * @return Cache
     * @throws \phpFastCache\Exceptions\phpFastCacheDriverCheckException
     */
    public static function instance($config = []) {
        $configHash = Arr::getHash($config);

        if (!array_key_exists($configHash, self::$instances)) {
            self::$instances[$configHash] = new self($config);
        }

        return self::$instances[$configHash];
    }

    /**
     * Cache constructor.
     *
     * @param array $config
     *
     * @throws \phpFastCache\Exceptions\phpFastCacheDriverCheckException
     */
    public function __construct(array $config) {
        $this->config = $config;

        $this->fastCache = new Psr16Adapter($config['driver'], $config['config'] ?: []);
    }

    /**
     * @param string $key
     * @param mixed $value
     *
     * @throws \phpFastCache\Exceptions\phpFastCacheSimpleCacheException
     */
    public function set($key, $value) {
        $this->fastCache->set($key, $value, ($this->config['lifetime'] ?: self::DEFAULT_LIFETIME) * self::SECONDS_IN_DAY);
    }

    /**
     * @param string $key
     * @param mixed|null $default
     *
     * @return mixed|null
     * @throws \phpFastCache\Exceptions\phpFastCacheSimpleCacheException
     */
    public function get($key, $default = NULL) {
        if ($this->fastCache->has($key)) {
            return $this->fastCache->get($key);
        }

        return $default;
    }

    /**
     * @param string $key
     *
     * @return bool
     * @throws \phpFastCache\Exceptions\phpFastCacheSimpleCacheException
     */
    public function has($key) {
        return $this->fastCache->has($key);
    }
}