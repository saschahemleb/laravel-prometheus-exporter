<?php

declare(strict_types=1);

namespace Saschahemleb\LaravelPrometheusExporter;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\QueryException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Prometheus\CollectorRegistry;
use Prometheus\Storage\Adapter;
use Saschahemleb\LaravelPrometheusExporter\Http\Controllers\MetricsController;
use Saschahemleb\LaravelPrometheusExporter\Listeners\ObserveDbQueryTime;

class PrometheusServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     */
    public function boot(): void
    {
        $this->registerRoutes();
        $this->registerEvents();

        if ($this->app->runningInConsole()) {
            $this->bootForConsole();
        }
    }

    /**
     * Register any package services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/prometheus.php', 'prometheus');

        $this->app->singleton(PrometheusExporter::class, function ($app) {
            $adapter = $app['prometheus.storage_adapter'];
            $prometheus = new CollectorRegistry($adapter, true);
            $exporter = new PrometheusExporter(config('prometheus.namespace'), $prometheus);
            foreach (config('prometheus.collectors') as $collectorClass) {
                $collector = $this->app->make($collectorClass);
                $exporter->registerCollector($collector);
            }
            return $exporter;
        });
        $this->app->alias(PrometheusExporter::class, 'prometheus');

        $this->app->bind('prometheus.storage_adapter_factory', function ($app) {
            return new StorageAdapterFactory($app['redis']);
        });

        $this->app->bind(Adapter::class, function ($app) {
            /* @var StorageAdapterFactory $factory */
            $factory = $app['prometheus.storage_adapter_factory'];
            $driver = config('prometheus.storage_adapter');
            $configs = config('prometheus.storage_adapters');
            $config = Arr::get($configs, $driver, []);

            return $factory->make($driver, $config);
        });
        $this->app->alias(Adapter::class, 'prometheus.storage_adapter');

        $this->app->singleton('prometheus.sql.histogram', function ($app) {
            return $app['prometheus']->getOrRegisterHistogram(
                'sql_query_duration_seconds',
                'SQL query duration histogram in seconds',
                [
                    'query',
                    'query_type'
                ],
                config('prometheus.sql_buckets')
            );
        });

        $this->app->bind(ObserveDbQueryTime::class, function ($app) {
            return new ObserveDbQueryTime(
                $app['prometheus.sql.histogram'],
                config('prometheus.collect_full_sql_query')
            );
        });

        $this->app->singleton('prometheus.sql_failed.counter', function ($app) {
            return $app['prometheus']->getOrRegisterCounter(
                'sql_failed_query_count',
                'SQL failed query count',
                [
                    'query',
                    'query_type'
                ]
            );
        });
        $this->app->singleton('prometheus.sql_failed.tracker', function ($app) {
            return new TrackFailedDbQuery(
                $app['prometheus.sql_failed.counter'],
                config('prometheus.collect_full_sql_query')
            );
        });

        $this->app->make(ExceptionHandler::class)->reportable(function (QueryException $e) {
            $this->app['prometheus.sql_failed.tracker']->handle(
                new QueryExecuted($e->getSql(), $e->getBindings(), null, DB::connection(config('database.default')))
            );
        });
    }

    protected function registerRoutes()
    {
        if (!config('prometheus.metrics_route_enabled')) {
            return;
        }

        Route::group([
            'namespace' => 'Saschahemleb\LaravelPrometheusExporter\Http\Controllers',
            'middleware' => config('prometheus.metrics_route_middleware', 'web'),
            'name' => 'prometheus.'
        ], function () {
            Route::get(
                config('prometheus.metrics_route_path'),
                [MetricsController::class, 'getMetrics']
            );
        });
    }

    protected function registerEvents()
    {
        $events = $this->app->make(Dispatcher::class);

        $events->listen(QueryExecuted::class, ObserveDbQueryTime::class);
    }

    /**
     * Console-specific booting.
     */
    protected function bootForConsole(): void
    {
        $this->publishes([
            __DIR__ . '/../config/prometheus.php' => config_path('prometheus.php'),
        ]);
    }
}
