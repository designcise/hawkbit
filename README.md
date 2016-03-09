# Blast Turbine

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![Build Status][ico-travis]][link-travis]
[![Total Downloads][ico-downloads]][link-downloads]
[![Coverage Status][ico-coveralls]][link-coveralls]

Turbine is a advanced derivate of [Proton](https://github.com/alexbilbie/Proton) and is a [PSR-7](https://github.com/php-fig/http-message) and [StackPHP](http://stackphp.com/) compatible micro framework.

Turbine uses latest versions of [League\Route](https://github.com/thephpleague/route) for routing, [League\Container](https://github.com/thephpleague/container) for dependency injection, and [League\Event](https://github.com/thephpleague/event) for event dispatching.

## Installation

Just add `"blast/turbine": "~1.0"` to your `composer.json` file.

## Setup

Create a new app

```php
<?php

require __DIR__.'/../vendor/autoload.php';

$app = new \Turbine\Application();
```

Create a new app with configuration

```php
$config = [
    'key' => 'value'
];
$app = new \Turbine\Application($config);
```

Add routes

```php
$app->get('/', function ($request, $response) {
    $response->getBody()->write('<h1>It works!</h1>');
    return $response;
});

$app->get('/hello/{name}', function ($request, $response, $args) {
    $response->getBody()->write(
        sprintf('<h1>Hello, %s!</h1>', $args['name'])
    );
    return $response;
});
```

Run application

```php
$app->run();
```

## Configuration

Extend configuration of an existing instance

```php
//add many values
$app->setConfig([
    'database' => 'mysql://root:root@localhost/acmedb',
    'services' => [
        'Acme\Services\ViewProvider',
    ]
]);

//add a single value
$app->setConfig('baseurl' => 'localhost/');
```

Access configuration

```php
//access all configuration
$app->getConfig();

//get one configuration item
$app->getConfig('database');
```

## IoC

Turbine allows access to most used services.




### Routing

Basic usage with anonymous functions:

```php
// index.php
<?php

$app->get('/', function ($request, $response) {
    $response->getBody()->write('<h1>It works!</h1>');
    return $response;
});

$app->get('/hello/{name}', function ($request, $response, $args) {
    $response->getBody()->write(
        sprintf('<h1>Hello, %s!</h1>', $args['name'])
    );
    return $response;
});

$app->run();
```

Basic usage with controllers:

```php
// index.php
<?php

require __DIR__.'/../vendor/autoload.php';

$app = new Turbine\Application();

$app->get('/', 'HomeController::index'); // calls index method on HomeController class

$app->run();
```

```php
// HomeController.php
<?php

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class HomeController
{
    public function index(ServerRequestInterface $request, ResponseInterface $response, array $args)
    {
        $response->getBody()->write('<h1>It works!</h1>');
        return $response;
    }
}
```

Constructor injections of controllers

```php
// index.php
<?php

require __DIR__.'/../vendor/autoload.php';

$app = new Turbine\Application();

$app->share('App\CustomService', new App\CustomService)
$app->get('/', 'HomeController::index'); // calls index method on HomeController class

$app->run();
```

```php
// HomeController.php
<?php

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class HomeController
{
    /**
     * @var App\CustomService
     */
    private $service;

    /**
     * @param App\CustomService $application
     */
    public function __construct(App\CustomService $service = null)
    {
        $this->service = $service;
    }

    /**
     * @return App\CustomService
     */
    public function getService()
    {
        return $this->service;
    }
    
    public function index(ServerRequestInterface $request, ResponseInterface $response, array $args)
    {
        //do somehing with service
        $service = $this->getService();
    
        $response->getBody()->write();
        return $response;
    }
}
```

## Middleware integrations

### StackPHP

Basic usage with StackPHP (using `Stack\Builder` and `Stack\Run`):

```php
// index.php
<?php
require __DIR__.'/../vendor/autoload.php';

$app = new Turbine\Application();

$app->get('/', function ($request, $response) {
    $response->setContent('<h1>Hello World</h1>');
    return $response;
});

$httpKernel = new Turbine\Symfony\HttpKernelAdapter($app);

$stack = (new Stack\Builder())
    ->push('Some/MiddleWare') // This will execute first
    ->push('Some/MiddleWare') // This will execute second
    ->push('Some/MiddleWare'); // This will execute third

$app = $stack->resolve($httpKernel);
Stack\run($httpKernel); // The app will run after all the middlewares have run
```

### Zend Stratigility

Basic usage with Stratigility (using `Zend\Stratigility\MiddlewarePipe`):

```php
$application = new Application();
$application->get('/', function($request, ResponseInterface $response){
    $response->getBody()->write('Hello World');
});
$middleware = new MiddlewarePipeAdapter($application);

//wrap html heading
$middleware->pipe('/', function($request, ResponseInterface $response, $next){
    $response->getBody()->write('<h1>');

    $response = $next($request, $response);

    $response->getBody()->write('</h1>');
});

$response = $middleware(ServerRequestFactory::fromGlobals(), $application->getResponse());

echo $response->getBody(); //prints <h1>Hello World</h1>

```

## Error handling

Turbine is using Whoops error handling framework and determines the error handler by request content type.

Set your own handler:

```php
$app->getErrorHandler()->push(new Acme\ErrorResponseHandler);
```

By default Turbine runs with error options disabled. To enable debugging add

```php
$app->setConfig('error', true);
```

By default Turbine is catching all errors. To disable error catching add

```php
$app->setConfig('error.catch', false);
```

## Logging

Turbine has built in support for Monolog. To access a channel call:

```php
$app->getLogger('channel name');
```

For more information about channels read this guide - [https://github.com/Seldaek/monolog/blob/master/doc/usage.md#leveraging-channels](https://github.com/Seldaek/monolog/blob/master/doc/usage.md#leveraging-channels).

## Events

You can intercept requests and responses at three points during the lifecycle:

### request.received

```php
$app->subscribe($app::EVENT_REQUEST_RECEIVED, function ($event, $request) {
    // manipulate request
});
```

This event is fired when a request is received but before it has been processed by the router.

### response.created

```php
$app->subscribe($app::EVENT_RESPONSE_CREATED, function ($event, $request, $response) {
    //manipulate request or response
});
```

This event is fired when a response has been created but before it has been output.

### response.sent

```php
$app->subscribe($app::EVENT_RESPONSE_SENT, function ($event, $request, $response) {
    //manipulate request and response
});
```

This event is fired when a response has been output and before the application lifecycle is completed.

### runtime.error

```php
$app->subscribe($app::EVENT_RUNTIME_ERROR, function ($event, $exception) use ($app) {
    //process exception
});
```

This event is always fired when an error occurs.

### lifecycle.error

`$errorResponse` is used as default response

```php
$app->subscribe($app::EVENT_LIFECYCLE_ERROR, function ($event, $exception, $errorResponse, $request, $response) {
    //manipulate $errorResponse and process exception
});
```

This event is fired only when an error occurs while handling request/response lifecycle. 
This event is fired after runtime.error

### lifecycle.complete

```php
$app->subscribe($app::EVENT_LIFECYCLE_COMPLETE, function ($event, $request, $response) {
    // access the request using $event->getRequest()
    // access the response using $event->getResponse()
})
```

This event is fired when a response has been output and before the application lifecycle is completed.

### Custom Events

You can fire custom events using the event emitter directly:

```php
// Subscribe
$app->subscribe('custom.event', function ($event, $time) {
    return 'the time is '.$time;
});

// Publish
$app->getEventEmitter()->emit('custom.event', time());
```

### Events from configuration

You can add configured events from configuration  

```php
//configure
$app->setConfig('events', function ($emitter, $app) {
    $emitter->addListener('custom.event', function ($event, $time) {
        return 'the time is '.$time;
    });
});
```

## Dependency Injection Container

Turbine uses `League/Container` as its dependency injection container.

You can bind singleton objects into the container from the main application object using ArrayAccess:

```php
$app['db'] = function () {
    $manager = new Illuminate\Database\Capsule\Manager;

    $manager->addConnection([
        'driver'    => 'mysql',
        'host'      => $config['db_host'],
        'database'  => $config['db_name'],
        'username'  => $config['db_user'],
        'password'  => $config['db_pass'],
        'charset'   => 'utf8',
        'collation' => 'utf8_unicode_ci'
    ], 'default');

    $manager->setAsGlobal();

    return $manager;
};
```

or by accessing the container directly:

```php
$app->getContainer()->share('db', function () {
    $manager = new Illuminate\Database\Capsule\Manager;

    $manager->addConnection([
        'driver'    => 'mysql',
        'host'      => $config['db_host'],
        'database'  => $config['db_name'],
        'username'  => $config['db_user'],
        'password'  => $config['db_pass'],
        'charset'   => 'utf8',
        'collation' => 'utf8_unicode_ci'
    ], 'default');

    $manager->setAsGlobal();

    return $manager;
});
```

Multitons can be added using the `add` method on the container:

```php
//callback
$app->getContainer()->add('foo', function () {
    return new Foo();
});
```

Service providers can be registered using the `register` method on the Turbine app or `addServiceProvider` on the container:

```php
$app->register('\My\Service\Provider');
$app->getContainer()->addServiceProvider('\My\Service\Provider');
```

For more information about service providers check out this page - [http://container.thephpleague.com/service-providers/](http://container.thephpleague.com/service-providers/).

For easy testing down the road it is recommended you embrace constructor injection:

```php
$app->getContainer()->add('Bar', function () {
        return new Bar();
});

$app->getContainer()->add('Foo', function () use ($app) {
        return new Foo(
            $app->getContainer()->get('Bar')
        );
});
```

### Services from configuration

You can add service from configuration  

```php
//configure
$app->setConfig('services', function ($container, $app) {
    $container->add('foo', function () {
        return new Foo();
    });;
});
```

## Change log

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Testing

``` bash
$ composer test
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email <mjls@web.de> instead of using the issue tracker.

## Credits

- [Marco Bunge](https://github.com/mbunge)
- [Alex Bilbie](https://github.com/alexbilbie) (Proton)
- [All contributors](https://github.com/phpthinktank/Turbine/graphs/contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/blast/turbine.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/phpthinktank/blast-turbine/master.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/blast/turbine.svg?style=flat-square
[ico-coveralls]: https://img.shields.io/coveralls/phpthinktank/blast-turbine/master.svg?style=flat-square)](https://coveralls.io/github/phpthinktank/blast-turbine?branch=1.0.x-dev

[link-packagist]: https://packagist.org/packages/blast/turbine
[link-travis]: https://travis-ci.org/phpthinktank/blast-turbine
[link-downloads]: https://packagist.org/packages/blast/turbine
[link-author]: https://github.com/mbunge
[link-contributors]: ../../contributors
[link-coveralls]: https://coveralls.io/github/phpthinktank/blast-turbine
