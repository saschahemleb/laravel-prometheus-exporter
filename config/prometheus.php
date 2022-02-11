<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Namespace
    |--------------------------------------------------------------------------
    |
    | The namespace to use as a prefix for all metrics.
    |
    | This will typically be the name of your project, eg: 'search'.
    |
    */

    'namespace' => env('PROMETHEUS_NAMESPACE', 'app'),

    /*
    |--------------------------------------------------------------------------
    | Metrics Route Enabled?
    |--------------------------------------------------------------------------
    |
    | If enabled, a /metrics route will be registered to export prometheus
    | metrics.
    |
    */

    'metrics_route_enabled' => env('PROMETHEUS_METRICS_ROUTE_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Metrics Route Path
    |--------------------------------------------------------------------------
    |
    | The path at which prometheus metrics are exported.
    |
    | This is only applicable if metrics_route_enabled is set to true.
    |
    */

    'metrics_route_path' => env('PROMETHEUS_METRICS_ROUTE_PATH', 'metrics'),

    /*
    |--------------------------------------------------------------------------
    | Metrics Route Middleware
    |--------------------------------------------------------------------------
    |
    | The middlewares for the metrics route.
    |
    | This is only applicable if metrics_route_enabled is set to true.
    */
    'metrics_route_middleware' => [],

    /*
    |--------------------------------------------------------------------------
    | Storage Adapter
    |--------------------------------------------------------------------------
    |
    | The storage adapter to use.
    |
    | Supported: "memory", "redis", "apc"
    |
    */

    'storage_adapter' => env('PROMETHEUS_STORAGE_ADAPTER', 'memory'),

    /*
    |--------------------------------------------------------------------------
    | Storage Adapters
    |--------------------------------------------------------------------------
    |
    | The storage adapter configs.
    |
    */

    'storage_adapters' => [
        'redis' => [
            'connection' => env('PROMETHEUS_REDIS_CONNECTION', 'default'),
            'prefix' => env('PROMETHEUS_REDIS_PREFIX', 'PROMETHEUS_'),
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Collect full SQL query
    |--------------------------------------------------------------------------
    |
    | Indicates whether we should collect the full SQL query or not.
    |
    */

    'collect_full_sql_query' => env('PROMETHEUS_COLLECT_FULL_SQL_QUERY', true),

    /*
    |--------------------------------------------------------------------------
    | Collectors
    |--------------------------------------------------------------------------
    |
    | The collectors specified here will be auto-registered in the exporter.
    |
    */

    'collectors' => [
        // \Your\ExporterClass::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Buckets config
    |--------------------------------------------------------------------------
    |
    | The buckets config specified here will be passed to the histogram generator
    | in the prometheus client. You can configure it as an array of time bounds.
    | Default value is null.
    |
    */

    'routes_buckets' => null,
    'sql_buckets' => null,
];
