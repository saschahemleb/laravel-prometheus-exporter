<?php

declare(strict_types=1);

namespace Saschahemleb\LaravelPrometheusExporter;

use Illuminate\Database\Events\QueryExecuted;
use Prometheus\Counter;

class TrackFailedDbQuery
{
    use CleansDbQueries;

    private $counter;
    private $collectFullSqlQuery;

    public function __construct(Counter $counter, bool $collectFullSqlQuery)
    {
        $this->counter = $counter;
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

        $this->counter->inc($labels);
    }
}