#!/usr/bin/env php
<?php

use Symfony\Component\Console\Application;

chdir(__DIR__);
require_once 'vendor/autoload.php';

$container = require 'config/container.php';
$application = new Application();

foreach ($container->get('commands') as $command) {
    $application->add($container->get($command));
}

$application->run();
