<?php

declare(strict_types=1);

namespace Saschahemleb\LaravelPrometheusExporter;

trait CleansDbQueries
{

    /**
     * Cleans the SQL string for registering the metric.
     * Removes repetitive question marks and simplifies "VALUES" clauses.
     */
    protected function cleanupSqlString(string $sql): string
    {
        $sql = preg_replace('/(VALUES\s*)(\([^\)]*+\)[,\s]*+)++/i', '$1()', $sql);
        $sql = preg_replace('/(\s*\?\s*,?\s*){2,}/i', '?', $sql);
        $sql = str_replace('"', '', $sql);

        return empty($sql) ? '[error]' : $sql;
    }
}