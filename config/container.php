<?php declare(strict_types=1);

use DI\ContainerBuilder;

$containerBuilder = new ContainerBuilder();
$containerBuilder->useAnnotations(true);

$containerBuilder->addDefinitions(__DIR__ . '/application.php');
$containerBuilder->addDefinitions(__DIR__ . '/definitions.php');
$containerBuilder->addDefinitions(__DIR__ . '/environment.php');

return $containerBuilder->build();
