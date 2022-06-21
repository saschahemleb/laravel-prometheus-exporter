# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]
### Added

### Removed

### Changed

## [1.0.2] - 2022-06-21
### Added
 - Add `sql_failed_query_count` metric, counting how many QueryExceptions were thrown

### Removed

### Changed
 - `sql_query_duration` was renamed to `sql_query_duration_seconds` and its unit was changed from milliseconds to seconds

## [1.0.1] - 2022-02-11
### Added
 - Laravel provider auto-discovery

### Removed

### Changed

## 1.0.0 - 2022-02-11
### Added
 - Fork from arquivei/laravel-prometheus-exporter
 - Support for Laravel v9.0
 - Allow configuration of middlewares for /metrics route

### Removed
 - Guzzle Middleware
 - Lumen Support + example project
 - `DatabaseServiceProvider`, Listener is now enabled by default and can not be disabled
 - Redis connection configuration. Use `storage_adapters.redis.connection` config instead

### Changed

[Unreleased]: https://github.com/saschahemleb/laravel-prometheus-exporter/compare/v1.0.2...main
[1.0.2]: https://github.com/saschahemleb/laravel-prometheus-exporter/compare/v1.0.1...v1.0.2
[1.0.1]: https://github.com/saschahemleb/laravel-prometheus-exporter/compare/v1.0.0...v1.0.1