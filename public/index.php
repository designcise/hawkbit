<?php
/**
 * Created by PhpStorm.
 * User: marco.bunge
 * Date: 21.08.2016
 * Time: 18:36
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
