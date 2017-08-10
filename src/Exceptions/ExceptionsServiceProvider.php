<?php
/**
 * Created by PhpStorm.
 * User: leemason
 * Date: 11/10/16
 * Time: 16:58
 */

namespace Ecfectus\Framework\Exceptions;

use Ecfectus\Container\ServiceProvider\AbstractServiceProvider;

class ExceptionsServiceProvider extends AbstractServiceProvider
{

    public $provides = [
        Handler::class
    ];

    public function register()
    {
        $this->share(Handler::class, function() {
            return new Handler();
        });
    }

}