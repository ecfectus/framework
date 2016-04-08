<?php

require __DIR__ . '/vendor/autoload.php';

$app = new \Ecfectus\Framework\Application(__DIR__);

print_r($app->get(\Ecfectus\Config\RepositoryInterface::class));