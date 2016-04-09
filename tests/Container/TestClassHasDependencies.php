<?php

namespace Ecfectus\Test\Container;

class TestClassHasDependencies
{
    public function __construct(\stdClass $argument)
    {
        $this->argument = $argument;
    }
}
