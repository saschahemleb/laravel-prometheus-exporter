<?php

declare(strict_types = 1);

namespace Saschahemleb\LaravelPrometheusExporter\Tests;

use Illuminate\Redis\Connections\Connection;
use Illuminate\Redis\RedisManager;
use InvalidArgumentException;
use Mockery\MockInterface;
use Orchestra\Testbench\TestCase;
use Prometheus\Storage\APC;
use Prometheus\Storage\InMemory;
use Prometheus\Storage\Redis;
use Saschahemleb\LaravelPrometheusExporter\StorageAdapterFactory;

/**
 * @covers \Saschahemleb\LaravelPrometheusExporter\StorageAdapterFactory<extended>
 */
class StorageAdapterFactoryTest extends TestCase
{
    /**
     * @var StorageAdapterFactory
     */
    private $factory;

    public function setUp() : void
    {
        parent::setUp();

        $this->instance(
            RedisManager::class,
            \Mockery::mock(RedisManager::class, function (MockInterface $mock) {
                $connection = \Mockery::mock(Connection::class, function (MockInterface $mock) {
                    $redis = \Mockery::mock(\Redis::class, function (MockInterface $mock) {
                        $mock->shouldReceive('isConnected')->andReturn(true);
                    });
                    $mock->shouldReceive('client')->andReturn($redis);
                });

                $mock->shouldReceive('connection')
                    ->with('special')
                    ->andReturn($connection);
            })
        );

        $this->factory = app(StorageAdapterFactory::class);
    }

    public function testMakeMemoryAdapter() : void
    {
        $adapter = $this->factory->make('memory');
        $this->assertInstanceOf(InMemory::class, $adapter);
    }

    public function testMakeApcAdapter() : void
    {
        $adapter = $this->factory->make('apc');
        $this->assertInstanceOf(APC::class, $adapter);
    }

    public function testMakeRedisAdapter() : void
    {
        $adapter = $this->factory->make('redis', ['connection' => 'special', 'prefix' => 'app_']);
        $this->assertInstanceOf(Redis::class, $adapter);
    }

    public function testMakeInvalidAdapter() : void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The driver [moo] is not supported.');
        $this->factory->make('moo');
    }
}
