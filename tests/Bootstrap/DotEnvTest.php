<?php
/**
 * Created by PhpStorm.
 * User: leemason
 * Date: 24/11/2016
 * Time: 21:51
 */

namespace Ecfectus\Framework\Test\Bootstrap;


use Ecfectus\Framework\Application;
use PHPUnit\Framework\TestCase;

class DotEnvTest extends TestCase
{

    public function testLoadsEnvValues(){

        $app = new Application(realpath(__DIR__ . '/../'));

        $app->bootstrap();

        $this->assertEquals('http://localhost', env('APP_URL', null));
    }

}