<?php
/**
 * Created by PhpStorm.
 * User: leemason
 * Date: 24/11/2016
 * Time: 21:37
 */

namespace Ecfectus\Framework\Bootstrap;


use Dotenv\Dotenv;
use Dotenv\Exception\InvalidPathException;
use Ecfectus\Framework\Application;
use Symfony\Component\Console\Input\ArgvInput;

class LoadEnvValues
{

    public function bootstrap(Application $app)
    {
        try{
            (new Dotenv($app->get('path'), $this->getEnvFile()))->load();
        }catch(InvalidPathException $e){
            //throw $e;
        }
    }

    protected function getEnvFile(){

        if (php_sapi_name() == 'cli') {
            $input = new ArgvInput();
            if ($input->hasParameterOption('--env')) {
                return $input->getParameterOption('--env');
            }
        }

        $file = env('APP_ENV');

        if ($file !== false) {
            return $file;
        }

        return null;
    }

}