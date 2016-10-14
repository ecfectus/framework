<?php

return [
    'providers' => [
        \Ecfectus\Framework\Http\HttpServiceProvider::class,
        \Ecfectus\Framework\Router\RouterServiceProvider::class,
        \Ecfectus\Framework\Pipeline\PipelineServiceProvider::class
    ],
    'test' => true
];