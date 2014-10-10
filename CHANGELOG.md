# Proton Changelog

## 1.0.4 (2014-10-10)

* Added `setExceptionDecorator` method
* Added `getEventEmitter` method

## 1.0.3 (2014-10-09)

* Added `$app['debug']` flag
 
## 1.0.2 (2014-10-06)

* Exception trace is an exploded string instead of an array (to prevent epic looping and object dumping)

## 1.0.1 (2014-10-03)

* The correct version of the request is passed through to the route dispatcher (which was using a new instance before)

## 1.0.0

First release!