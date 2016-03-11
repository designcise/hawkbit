# Blast Turbine Changelog

## 1.1.0

### Altered

 - Upgrade `league/route` from dev-develop to stable 2.x (`~2.0`) release

## 1.1.0

### Added

 - Add `filp/whoops` as default error handler
 - Add `zendframework/zend-stratigility` integration

### Altered

 - add request and response accessors
 - refactor error handling and replace exception decorator with whoops
 - pass and receive all config 
 - remove possibilty to configure events, routes and services by callables
 - rename `Turbine\Psr7\TerminableInterface` to `Turbine\TerminableInterface`
 - rename debug config option to error
 - change configuration engine from `array` to instance of `\ArrayAccess`
 - Signature changes of `Turbine\Application::handle`, `Turbine\Application::run`, `Turbine\Application::__construct`, `Turbine\Application::handleErrors` 

### Removed

 - `Turbine\Psr7\HttpKernelInterface` replaced by `Turbine\ApplicationInterface`
 
## 1.0.0 (2016-03-04)

### Added

 - `Turbine\Psr7\HttpKernelInterface` and `Turbine\Psr7\TerminableInterface` port of symfony HttpKernelInterface for PSR-7 compatibility
 - Add `zend/diactoros` for PSR-7 http support
 - provide compatibility with adapter `Turbine\Symfony\HttpKernelAdapter` for StackPHP and other Symfony HttpKernelInterface implementations
 - Add `filp/whoops` as default error handler
 - Add `zendframework/zend-stratigility` integration

### Altered

 - upgrade `league/container` to latest version 2 and add interopt compatibility
 - upgrade `league/route` to latest version 2 (currently under development)
 - replace symfony request and response with diactoros request and response
 - enable auto wiring of container configurable and enable by default
 - events, routes and services configurable by callables
 - add request and response accessors
 - refactor error handling and replace exception decorator with whoops
 - enhance configuration handling 
