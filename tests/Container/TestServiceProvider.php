<?php

namespace Ecfectus\Test\Container;

use Ecfectus\Container\ServiceProvider\AbstractServiceProvider;

class TestServiceProvider extends AbstractServiceProvider
{
    protected $provides = [
      \stdClass::class
    ];

    public function register()
    {
        $this->bind(\stdClass::class, function () {
            return new \stdClass();
        });
    }
}
