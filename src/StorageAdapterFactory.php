<?php

declare(strict_types = 1);

namespace Saschahemleb\LaravelPrometheusExporter;

use Illuminate\Redis\RedisManager;
use Illuminate\Support\Arr;
use InvalidArgumentException;
use Prometheus\Storage\Adapter;
use Prometheus\Storage\APC;
use Prometheus\Storage\InMemory;
use Prometheus\Storage\Redis;

class StorageAdapterFactory
{
    private $redisManager;

    public function __construct(RedisManager $redisManager)
    {
        $this->redisManager = $redisManager;
    }

    /**
     * Factory a storage adapter.
     *
     * @param string $driver
     * @param array  $config
     *
     * @return Adapter
     */
    public function make(string $driver, array $config = []) : Adapter
    {
        switch ($driver) {
            case 'memory':
                return new InMemory();
            case 'redis':
                return $this->makeRedisAdapter($config);
            case 'apc':
                return new APC();
        }

        throw new InvalidArgumentException(sprintf('The driver [%s] is not supported.', $driver));
    }

    /**
     * Factory a redis storage adapter.
     *
     * @param array $config
     *
     * @return Redis
     */
    protected function makeRedisAdapter(array $config) : Redis
    {
        if (isset($config['prefix'])) {
            Redis::setPrefix($config['prefix']);
        }

        $connection = Arr::get($config, 'connection');

        return Redis::fromExistingConnection($this->redisManager->connection($connection)->client());
    }
}
