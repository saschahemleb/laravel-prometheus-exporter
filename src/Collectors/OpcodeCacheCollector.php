<?php

declare(strict_types=1);

namespace Saschahemleb\LaravelPrometheusExporter\Collectors;

use Prometheus\Gauge;
use Saschahemleb\LaravelPrometheusExporter\PrometheusExporter;
use Saschahemleb\LaravelPrometheusExporter\Contracts\CollectorInterface;

class OpcodeCacheCollector implements CollectorInterface
{
    /**
     * @type ?Gauge
     */
    private $info;
    /**
     * @type ?Gauge
     */
    private $full;
    /**
     * @type ?Gauge
     */
    private $restart;
    /**
     * @type ?Gauge
     */
    private $memoryTotal;
    /**
     * @type ?Gauge
     */
    private $memoryUsed;
    /**
     * @type ?Gauge
     */
    private $memoryWasted;
    /**
     * @type ?Gauge
     */
    private $memoryWastedPercentage;
    /**
     * @type ?Gauge
     */
    private $cachedKeysTotal;
    /**
     * @type ?Gauge
     */
    private $cachedKeysUsed;
    /**
     * @type ?Gauge
     */
    private $restarts;
    /**
     * @type ?Gauge
     */
    private $cacheHitsTotal;
    /**
     * @type ?Gauge
     */
    private $cacheMissesTotal;

    /**
     * Return the name of the collector.
     *
     * @return string
     */
    public function getName() : string
    {
        return 'opcode-cache';
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
    public function registerMetrics(PrometheusExporter $exporter): void
    {
        $this->info = $exporter->registerGauge(
            'php_opcache_info',
            'Information about the PHP opcode cache.',
            ['version']
        );
        $this->full = $exporter->registerGauge(
            'php_opcache_full',
            'Whether the cache is full or not'
        );
        $this->restart = $exporter->registerGauge(
            'php_opcache_restart',
            'Tracks if a restart is pending or in progress',
            ['type']
        );
        $this->restarts = $exporter->registerGauge(
            'php_opcache_restarts',
            'How often the OPcache had to be restarted',
            ['kind']
        );
        $this->memoryTotal = $exporter->registerGauge(
            'php_opcache_memory_total_bytes',
            'The size of the shared memory storage used by OPcache, in bytes'
        );
        $this->memoryUsed = $exporter->registerGauge(
            'php_opcache_memory_used_bytes',
            'The amount of memory used by OPcache, in bytes'
        );
        $this->memoryWasted = $exporter->registerGauge(
            'php_opcache_memory_wasted_bytes',
            'The amount of wasted memory, in bytes'
        );
        $this->memoryWastedPercentage = $exporter->registerGauge(
            'php_opcache_memory_wasted_ratio',
            'The amount of wasted memory, in a ratio between 0-1'
        );
        $this->cachedKeysTotal = $exporter->registerGauge(
            'php_opcache_cached_keys_total',
            'The maximum number of keys in the OPcache hash table'
        );
        $this->cachedKeysUsed = $exporter->registerGauge(
            'php_opcache_cached_keys_used',
            'The amount of keys in the OPcache hash table'
        );
        $this->cacheHitsTotal = $exporter->registerGauge(
            'php_opcache_cache_hits_total',
            'The amount of cache hits'
        );
        $this->cacheMissesTotal = $exporter->registerGauge(
            'php_opcache_cache_misses_total',
            'The amount of cache misses'
        );
    }

    /** @noinspection PhpComposerExtensionStubsInspection */
    public function collect(): void
    {
        if (!function_exists('opcache_get_configuration')) {
            // short circuit if opcache is loaded
            $this->info->set(0, ['']);
            return;
        }

        $config = opcache_get_configuration();
        $opcacheEnabled = $config['directives']['opcache.enable'];
        $this->info->set($opcacheEnabled ? 1 : 0, [$config['version']['version']]);
        if ($opcacheEnabled === false) {
            // short circuit if opcache is not enabled
            return;
        }

        $status = opcache_get_status(false);

        $this->full->set($status['cache_full'] ? 1 : 0);
        $this->restart->set($status['restart_pending'] ? 1 : 0, ["pending"]);
        $this->restart->set($status['restart_in_progress'] ? 1 : 0, ["progressing"]);

        $this->memoryTotal->set($config['directives']['opcache.memory_consumption']);
        $this->memoryUsed->set($status['memory_usage']['used_memory']);
        $this->memoryWasted->set($status['memory_usage']['wasted_memory']);
        $this->memoryWastedPercentage->set($status['memory_usage']['current_wasted_percentage']);
        $this->restarts->set($status['opcache_statistics']['oom_restarts'], ['oom']);

        $this->cachedKeysTotal->set($status['opcache_statistics']['max_cached_keys']);
        $this->cachedKeysUsed->set($status['opcache_statistics']['num_cached_keys']);
        $this->restarts->set($status['opcache_statistics']['hash_restarts'], ['hash']);

        $this->cacheHitsTotal->set($status['opcache_statistics']['hits']);
        $this->cacheMissesTotal->set($status['opcache_statistics']['misses']);
    }
}