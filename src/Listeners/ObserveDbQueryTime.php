<?php

declare(strict_types=1);

namespace Saschahemleb\LaravelPrometheusExporter\Listeners;

use Illuminate\Database\Events\QueryExecuted;
use Prometheus\Histogram;
use Saschahemleb\LaravelPrometheusExporter\CleansDbQueries;

class ObserveDbQueryTime
{
    use CleansDbQueries;

    private $histogram;
    private $collectFullSqlQuery;

    public function __construct(Histogram $histogram, bool $collectFullSqlQuery)
    {
        $this->histogram = $histogram;
        $this->collectFullSqlQuery = $collectFullSqlQuery;
    }

    public function handle(QueryExecuted $query)
    {
        $type = strtoupper(strtok($query->sql, ' '));

        $querySql = $this->collectFullSqlQuery ? $this->cleanupSqlString($query->sql) : '[omitted]';

        $labels = array_values(array_filter([
            $querySql,
            $type
        ]));

        $this->histogram->observe($query->time / 1e3, $labels);
    }
}