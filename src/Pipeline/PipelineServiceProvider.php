<?php
/**
 * Created by PhpStorm.
 * User: leemason
 * Date: 11/10/16
 * Time: 18:02
 */

namespace Ecfectus\Framework\Pipeline;


use Ecfectus\Container\ContainerInterface;
use Ecfectus\Container\ServiceProvider\AbstractServiceProvider;
use Ecfectus\Pipeline\FirstArgumentPipeline;
use Ecfectus\Pipeline\LastArgumentPipeline;
use Ecfectus\Pipeline\Pipeline;
use Ecfectus\Pipeline\PipelineInterface;

class PipelineServiceProvider extends AbstractServiceProvider
{

    public $provides = [
        PipelineInterface::class,
        Pipeline::class,
        FirstArgumentPipeline::class,
        LastArgumentPipeline::class
    ];

    public function register()
    {
        $this->bind(PipelineInterface::class, [function(ContainerInterface $app) {

            return (new Pipeline())->setResolver(function($callback = null) use ($app){
                return $app->resolve($callback);
            });

        }, ContainerInterface::class]);

        $this->bind(Pipeline::class, [function(ContainerInterface $app) {

            return (new Pipeline())->setResolver(function($callback = null) use ($app){
                return $app->resolve($callback);
            });

        }, ContainerInterface::class]);

        $this->bind(LastArgumentPipeline::class, [function(ContainerInterface $app) {

            return (new LastArgumentPipeline())->setResolver(function($callback = null) use ($app){
                return $app->resolve($callback);
            });

        }, ContainerInterface::class]);

        $this->bind(FirstArgumentPipeline::class, [function(ContainerInterface $app) {

            return (new LastArgumentPipeline())->setResolver(function($callback = null) use ($app){
                return $app->resolve($callback);
            });

        }, ContainerInterface::class]);
    }
}