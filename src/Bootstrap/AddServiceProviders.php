<?php
/**
 * Created by PhpStorm.
 * User: leemason
 * Date: 10/08/2017
 * Time: 14:53
 */

namespace Ecfectus\Framework\Bootstrap;


use Ecfectus\Framework\Application;
use Ecfectus\Framework\Config\RepositoryInterface;

class AddServiceProviders
{

    public function bootstrap(Application $app)
    {
        $providers = array_merge(
            $app->get(RepositoryInterface::class)->get('app.providers', []),
            $app->get(RepositoryInterface::class)->get('app.'.env('APP_ENV', 'dev').'_providers', [])
        );

        foreach($providers as $provider){
            $app->addServiceProvider($provider);
        }

        $app->bootServiceProviders();
    }

}