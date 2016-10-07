<?php

namespace SimpleFilemanager\Implementation\Silex;

use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;
use Silex\ServiceControllerResolver;
use Silex\ServiceProviderInterface;
use SimpleFilemanager\Implementation\Silex\Controllers\Filemanager;
use SimpleFilemanager\Lib\SimpleFilemanager;

class SimpleFilemanagerProvider implements ControllerProviderInterface
{


    /**
     * Returns routes to connect to the given application.
     *
     * @param Application $app An Application instance
     *
     * @return ControllerCollection A ControllerCollection instance
     */
    public function connect(Application $app)
    {
        $app['simple-filemanager.controller'] = $app->share(function ($app) {
            return new Filemanager();
        });

        $app['simple-filemanager.service'] = $app->share(function ($app) {
            return new SimpleFilemanager($app['sfm-options.root_dir']);
        });

        if (isset($app['twig'])) {
            $loader = new \Twig_Loader_Filesystem();
            $loader->addPath(__DIR__ . '/Resources/views', 'simple-filemanager');
            $app['twig']->getLoader()->addLoader($loader);
        }

        /** @var ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];
        $controllers->get('/upload/{directory}', 'simple-filemanager.controller:uploadAction')
            ->value('directory', null)
            ->assert('directory', '.*')
            ->method('POST')->bind('simple-filemanager.upload');
        $controllers->get('/open/{path}', 'simple-filemanager.controller:openAction')
            ->bind('simple-filemanager.open')
            ->assert('path', '.*');
        $controllers->get('/operation/{type}/{path}', 'simple-filemanager.controller:operationAction')
            ->value('path', null)
            ->assert('path', '.*')
            ->method('POST')
            ->bind('simple-filemanager.operation');
         $controllers->get('/{directory}', 'simple-filemanager.controller:listAction')
            ->value('directory', null)
            ->assert('directory', '.*')
            ->bind('simple-filemanager.overview');



        return $controllers;
    }

    /**
     * Bootstraps the application.
     *
     * This method is called after all services are registered
     * and should be used for "dynamic" configuration (whenever
     * a service must be requested).
     */
    public function boot(Application $app)
    {
        // TODO: Implement boot() method.
    }
}