# Blast Turbine Changelog

## 1.1.0

### Added

 - Add `filp/whoops` as default error handler
 - Add `zendframework/zend-stratigility` integration

### Altered

 - add request and response accessors
 - refactor error handling and replace exception decorator with whoops
 - enhance configuration handling 
 - remove possibilty to configure events, routes and services by callables
 
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
 - replace symfony request and response with diacoros request and response
 - enable auto wiring of container configurable and enable by default
 - events, routes and services configurable by callables
 - add request and response accessors
 - refactor error handling and replace exception decorator with whoops
 - enhance configuration handling 