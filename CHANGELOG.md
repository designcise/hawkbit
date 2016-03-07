# Blast Turbine Changelog

## 1.0.0 (2016-03-04)

### Added

- `Turbine\Psr7\HttpKernelInterface` and `Turbine\Psr7\TerminableInterface` port of symfony HttpKernelInterface for PSR-7 compatibility
- Add `zend/diactoros` for PSR-7 http support
- provide compatibility with adapter `Turbine\Symfony\HttpKernelAdapter` for StackPHP and other Symfony HttpKernelInterface implementations

### Altered

 - upgrade `league/container` to latest version 2 and add interopt compatibility
 - upgrade `league/route` to latest version 2 (currently under development)
 - replace symfony request and response with diacoros request and response
 - enable auto wiring of container configurable and enable by default
 - events, routes and services configurable by callables 