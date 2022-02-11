<?php

declare(strict_types=1);

namespace Saschahemleb\LaravelPrometheusExporter\Listeners;

use Illuminate\Database\Events\QueryExecuted;
use Prometheus\Histogram;

class ObserveDbQueryTime
{
    private $histogram;
    private $collectFullSqlQuery;

    public function __construct(Histogram $histogram, bool $collectFullSqlQuery)
    {
        $this->histogram = $histogram;
        $this->collectFullSqlQuery = $collectFullSqlQuery;
    }

    public function handle(QueryExecuted $query)
    {
        $querySql = '[omitted]';
        $type = strtoupper(strtok($query->sql, ' '));
        if ($this->collectFullSqlQuery) {
            $querySql = $this->cleanupSqlString($query->sql);
        }

        $labels = array_values(array_filter([
            $querySql,
            $type
        ]));

        $this->histogram->observe($query->time, $labels);
    }

    /**
     * Cleans the SQL string for registering the metric.
     * Removes repetitive question marks and simplifies "VALUES" clauses.
     *
     * @return string
     */
    private function cleanupSqlString(string $sql): string
    {
        $sql = preg_replace('/(VALUES\s*)(\([^\)]*+\)[,\s]*+)++/i', '$1()', $sql);
        $sql = preg_replace('/(\s*\?\s*,?\s*){2,}/i', '?', $sql);
        $sql = str_replace('"', '', $sql);

        return empty($sql) ? '[error]' : $sql;
    }
}