<?php

use Composer\Autoload\ClassLoader;

$loader = new ClassLoader();
$loader->addPsr4('UksusoFF\\WebtreesModules\\Photos\\', __DIR__ . '/src');
$loader->register();
