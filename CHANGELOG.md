# Blast Turbine Changelog

## 1.1.6

### Added

 - Add vagrant development environment
 - Add shutdown event
 - Add logic to force response emitting if headers already send

### Altered

 - Delegate request content type to response
 - Rename `Application::cleanUp` to `Application::collectGarbage`
 - Rename `Application::finishRequest` to `Application::shutdown`
 - Rename `Application::subscribe` to `Application::addListener`
 - Enhance error handling for different content types
 
### Deprecated

 - `Application::cleanUp`
 - `Application::finishRequest`
 - `Application::subscribe`


## 1.1.5

### Added

 - Add `\Turbine\Application\ConfiguratorInterface`

### Altered

 - `Application::getConfigurator` is now bound to `\Turbine\Application\ConfiguratorInterface` contract

## 1.1.4

### Fixes

- [\#9](../../issues/9) If class exists and is not part of container, `League\Container\Container::has` returns now false.

## 1.1.3

### Altered

- Accept and process `\ArrayAccess` and `\Traversable` as configuration

## 1.1.2

### Altered

 - Replace applications [route collection methods](https://github.com/thephpleague/route/blob/master/src/RouteCollectionInterface.php) with `\League\Route\RouteCollectionMapTrait`
 - Application implements `\League\Route\RouteCollectionInterface`
 - add `\League\Route\RouteCollectionInterface::map()` 
 - add `\Turbine\Application::group()` for creating route groups, see [documentation](http://route.thephpleague.com/route-groups/)

### Deprecated

 - `\Turbine\Application::subscribe()`

## 1.1.1

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