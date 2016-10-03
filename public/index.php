<?php
/**
 * The Turbine Micro Framework. An advanced derivate of Proton Micro Framework
 *
 * @author Marco Bunge <marco_bunge@web.de>
 * @copyright Marco Bunge <marco_bunge@web.de>
 *
 * @license MIT
 */

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Turbine\Application;

require_once __DIR__ . '/../vendor/autoload.php';

$app = new Application();

$app->get('/', function (ServerRequestInterface $request, ResponseInterface $response) {
    $response->getBody()->write('<h1>It works!</h1>');
    return $response;
});

$app->run();
