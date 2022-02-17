# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]
### Added

### Removed

### Changed

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

[Unreleased]: https://github.com/saschahemleb/laravel-prometheus-exporter/compare/v1.0.1...main
[1.0.1]: https://github.com/saschahemleb/laravel-prometheus-exporter/compare/v1.0.0...v1.0.1