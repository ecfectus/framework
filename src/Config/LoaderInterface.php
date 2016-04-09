<?php namespace Ecfectus\Config;

interface LoaderInterface
{
    public function load($environment, $group);
}
