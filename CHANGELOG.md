# Electron Changelog

## 1.0.0 (2016-03-04)

### Added

- `Electron\Psr7\HttpKernelInterface` and `Electron\Psr7\TerminableInterface` port of symfony HttpKernelInterface for PSR-7 compatibility
- Add `zend/diactoros` for PSR-7 http support
- provide compatibility with adapter `Electron\Symfony\HttpKernelAdapter` for StackPHP and other Symfony HttpKernelInterface implementations

### Altered

 - upgrade `league/container` to latest version 2 and add interopt compatibility
 - upgrade `league/route` to latest version 2 (currently under development)
 - replace symfony request and response with diacoros request and response
 - enable auto wiring of container configurable and enable by default
 - events, routes and services configurable by callables 