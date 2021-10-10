<?php declare(strict_types=1);

use App\Http\RequestHandler;
use Doctrine\ORM\EntityManagerInterface;
use Spiral\Goridge\StreamRelay;
use Spiral\RoadRunner\PSR7Client;
use Spiral\RoadRunner\Worker;

error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

chdir(__DIR__);
require_once 'vendor/autoload.php';

$container = require 'config/container.php';
$requestHandler = $container->get(RequestHandler::class);
$client = new PSR7Client(new Worker(new StreamRelay(STDIN, STDOUT)));

while ($request = $client->acceptRequest()) {
    $client->respond($requestHandler->handle($request));

    // See: https://github.com/spiral/roadrunner/wiki/Production-Usage
    $entityManager = $container->get(EntityManagerInterface::class);
    $entityManager->clear();
    $entityManager->getConnection()->close();
    gc_collect_cycles();
}
