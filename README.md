# Laravel Prometheus Exporter

A prometheus exporter package for Laravel.

[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)

## Introduction

Prometheus is a time-series database with a UI and sophisticated querying language (PromQL) that can scrape metrics, counters, gauges and histograms over HTTP.

This package is a wrapper bridging [promphp/prometheus_client_php](https://github.com/promphp/prometheus_client_php) into Laravel.

## Installation

Install the package via composer
```bash
composer require saschahemleb/laravel-prometheus-exporter
```

Please see below for instructions on how to enable metrics on Application routes.

## Configuration

The package has a default configuration which uses the following environment variables.
```
PROMETHEUS_NAMESPACE=app

PROMETHEUS_METRICS_ROUTE_ENABLED=true
PROMETHEUS_METRICS_ROUTE_PATH=metrics
PROMETHEUS_COLLECT_FULL_SQL_QUERY=true
PROMETHEUS_STORAGE_ADAPTER=memory

PROMETHEUS_REDIS_CONNECTION=default
PROMETHEUS_REDIS_PREFIX=PROMETHEUS_
```

To customize the configuration values you can either override the environment variables above (usually this is done in your application's `.env` file), or you can publish the included [prometheus.php](config/prometheus.php)
to `config/prometheus.php`.
```bash
$ php artisan vendor:publish --provider "Saschahemleb\\LaravelPrometheusExporter\\PrometheusServiceProvider"
```

## Metrics

The package allows you to observe metrics on:

* Application routes. Metrics on request method, request path and status code.
* SQL queries. Metrics on SQL query and query type.

In order to observe metrics in application routes (the time between a request and response),
you should register the following middleware in your application's `app/Http/Kernel.php`:
```php
protected $middleware = [
    \Saschahemleb\LaravelPrometheusExporter\Http\Middleware\ObserveResponseTime::class,
    // [...]
];
```

The labels exported are

```php
[
    'method',
    'route',
    'status_code',
]
```

SQL metrics are observed ootb.
The labels exported are

```php
[
    'query',
    'query_type',
]
```

Note: you can disable logging the full query by turning off the configuration of `PROMETHEUS_COLLECT_FULL_SQL_QUERY`.

### Storage Adapters

The storage adapter is used to persist metrics across requests.  The `memory` adapter is enabled by default, meaning
data will only be persisted across the current request.

We recommend using the `redis` or `apc` adapter in production
environments. Of course your installation has to provide a Redis or APC implementation.

The `PROMETHEUS_STORAGE_ADAPTER` environment variable is used to specify the storage adapter.

## Exporting Metrics

The package adds a `/metrics` endpoint, enabled by default, which exposes all metrics gathered by collectors.

This can be turned on/off using the `PROMETHEUS_METRICS_ROUTE_ENABLED` environment variable,
and can also be changed using the `PROMETHEUS_METRICS_ROUTE_PATH` environment variable.

## Collectors

A collector is a class, implementing the [CollectorInterface](src/CollectorInterface.php), which is responsible for
collecting data for one or many metrics.

Please see the [Example](#Collector) included below.

You can auto-load your collectors by adding them to the `collectors` array in the `prometheus.php` config.

## Examples

### Collector

This is an example collector implementation:

```php
<?php

declare(strict_types = 1);

namespace Saschahemleb\LaravelPrometheusExporter;

use Prometheus\Gauge;use Saschahemleb\LaravelPrometheusExporter\Contracts\CollectorInterface;

class ExampleCollector implements CollectorInterface
{
    /**
     * @var Gauge
     */
    protected $usersRegisteredGauge;

    /**
     * Return the name of the collector.
     *
     * @return string
     */
    public function getName() : string
    {
        return 'users';
    }

    /**
     * Register all metrics associated with the collector.
     *
     * The metrics needs to be registered on the exporter object.
     * eg:
     * ```php
     * $exporter->registerCounter('search_requests_total', 'The total number of search requests.');
     * ```
     *
     * @param PrometheusExporter $exporter
     */
    public function registerMetrics(PrometheusExporter $exporter) : void
    {
        $this->usersRegisteredGauge = $exporter->registerGauge(
            'users_registered_total',
            'The total number of registered users.',
            ['group']
        );
    }

    /**
     * Collect metrics data, if need be, before exporting.
     *
     * As an example, this may be used to perform time consuming database queries and set the value of a counter
     * or gauge.
     */
    public function collect() : void
    {
        // retrieve the total number of staff users registered
        // eg: $totalUsers = Users::where('group', 'staff')->count();
        $this->usersRegisteredGauge->set(36, ['staff']);

        // retrieve the total number of regular users registered
        // eg: $totalUsers = Users::where('group', 'regular')->count();
        $this->usersRegisteredGauge->set(192, ['regular']);
    }
}
```
