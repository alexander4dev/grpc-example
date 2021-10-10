<?php

declare(strict_types=1);

use App\Http\RequestHandler;

header("Access-Control-Allow-Origin: *");
//die(var_dump(get_headers('http://192.168.50.106')));
if (file_exists($fileName = trim($_SERVER['REQUEST_URI'], '/'))) {
    header("Content-Type: application/json");
    echo file_get_contents($fileName);
    return;
}

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PATCH, DELETE, PUT");
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
        header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
    exit(0);
}

chdir(__DIR__);
require_once 'vendor/autoload.php';

$container = require 'config/container.php';
/* @var $handler RequestHandler */
$handler = $container->get(RequestHandler::class);

/* @var $logger \Monolog\Logger */
$logger = $container->get(\Psr\Log\LoggerInterface::class);
//$logger->info('', []);

$response = $handler->handle(\Zend\Diactoros\ServerRequestFactory::fromGlobals());

$http_line = sprintf('HTTP/%s %s %s',
    $response->getProtocolVersion(),
    $response->getStatusCode(),
    $response->getReasonPhrase()
);
header($http_line, true, $response->getStatusCode());

foreach ($response->getHeaders() as $name => $values) {
    foreach ($values as $value) {
        header("$name: $value", false);
    }
}

$stream = $response->getBody();

if ($stream->isSeekable()) {
    $stream->rewind();
}

while (!$stream->eof()) {
    echo $stream->read(1024 * 8);
}
