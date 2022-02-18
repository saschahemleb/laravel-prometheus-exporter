<?php

declare(strict_types = 1);

namespace Saschahemleb\LaravelPrometheusExporter\Tests;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase;
use Prometheus\Counter;
use Prometheus\Histogram;
use Prometheus\Storage\Adapter;
use Saschahemleb\LaravelPrometheusExporter\PrometheusExporter;
use Saschahemleb\LaravelPrometheusExporter\PrometheusServiceProvider;
use Saschahemleb\LaravelPrometheusExporter\StorageAdapterFactory;
use Saschahemleb\LaravelPrometheusExporter\Tests\Fixture\MetricSamplesSpec;

/**
 * @covers \Saschahemleb\LaravelPrometheusExporter\PrometheusServiceProvider<extended>
 * @covers \Saschahemleb\LaravelPrometheusExporter\Listeners\ObserveDbQueryTime
 */
class PrometheusServiceProviderTest extends TestCase
{
    private $createdTable = false;

    public function testServiceProvider() : void
    {
        $this->assertInstanceOf(Adapter::class, $this->app[Adapter::class]);
        $this->assertInstanceOf(PrometheusExporter::class, $this->app[PrometheusExporter::class]);
        $this->assertInstanceOf(StorageAdapterFactory::class, $this->app[StorageAdapterFactory::class]);

        $this->assertInstanceOf(Adapter::class, $this->app->get('prometheus.storage_adapter'));
        $this->assertInstanceOf(PrometheusExporter::class, $this->app->get('prometheus'));
        $this->assertInstanceOf(StorageAdapterFactory::class, $this->app->get('prometheus.storage_adapter_factory'));

        /* @var \Illuminate\Support\Facades\Route $router */
        $router = $this->app['router'];
        $this->assertNotEmpty($router->get('metrics'));

        /* @var \Illuminate\Support\Facades\Config $config  */
        $config = $this->app['config'];
        $this->assertTrue($config->get('prometheus.metrics_route_enabled'));
        $this->assertEmpty($config->get('prometheus.metrics_route_middleware'));
        $this->assertSame([], $config->get('prometheus.collectors'));
        $this->assertEquals('memory', $config->get('prometheus.storage_adapter'));
    }

    public function testServiceProviderWithDefaultConfig() : void
    {
        $this->createTestTable();

        /* @var \Prometheus\Histogram $histogram */
        $histogram = $this->app->get('prometheus.sql.histogram');
        $this->assertInstanceOf(Histogram::class, $histogram);
        $this->assertSame(['query', 'query_type'], $histogram->getLabelNames());
        $this->assertSame('app_sql_query_duration_seconds', $histogram->getName());
        $this->assertSame('SQL query duration histogram in seconds', $histogram->getHelp());

        /* @var PrometheusExporter $prometheus */
        $prometheus = $this->app->get('prometheus');
        $export = $prometheus->export();

        $this->assertContainsSamplesMatching(
            $export,
            MetricSamplesSpec::create()
                ->withName('app_sql_query_duration_seconds')
                ->withLabelNames(['query', 'query_type'])
                ->withHelp('SQL query duration histogram in seconds')
        );
    }

    public function testServiceProviderWithoutCollectingFullSqlQueries()
    {
        $this->app->get('config')->set('prometheus.collect_full_sql_query', false);
        $this->createTestTable();

        /* @var \Prometheus\Histogram $histogram */
        $histogram = $this->app->get('prometheus.sql.histogram');
        $this->assertInstanceOf(Histogram::class, $histogram);
        $this->assertSame(['query', 'query_type'], $histogram->getLabelNames());

        /* @var PrometheusExporter $prometheus */
        $prometheus = $this->app->get('prometheus');
        $export = $prometheus->export();
        $this->assertContainsSamplesMatching(
            $export,
            MetricSamplesSpec::create()
                ->withLabelNames(['query', 'query_type'])
        );
    }

    public function testSqlFailedCounter()
    {
        /** @var ExceptionHandler $handler */
        $handler = $this->app->make(ExceptionHandler::class);
        try {
            DB::table('invalid table name')->first();
        } catch (QueryException $exception) {
            $handler->report($exception);
        }

        $counter = $this->app->get('prometheus.sql_failed.counter');
        $this->assertInstanceOf(Counter::class, $counter);
        $this->assertSame(['query', 'query_type'], $counter->getLabelNames());

        /* @var PrometheusExporter $prometheus */
        $prometheus = $this->app->get('prometheus');
        $export = $prometheus->export();
        $this->assertContainsSamplesMatching(
            $export,
            MetricSamplesSpec::create()
                ->withLabelNames(['query', 'query_type']),
            1,
            'Expected prometheus export to contain `sql_failed_query_count{query,query_type}`',
        );
    }

    protected function createTestTable()
    {
        $this->createdTable = false;
        Schema::connection('test')->create('test', function($table)
        {
            $table->increments('id');
            $table->timestamps();
            $this->createdTable = true;
        });

        while (!$this->createdTable) {
            continue;
        }
    }

    protected function getPackageProviders($app) : array
    {
        return [PrometheusServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'test');
        $app['config']->set('database.connections.test', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    private function assertContainsSamplesMatching(array $samples, MetricSamplesSpec $spec, int $count = 1, string $message = ''): void
    {
        $matched = array_filter($samples, [$spec, 'matches']);
        $this->assertCount($count, $matched, $message);
    }
}
