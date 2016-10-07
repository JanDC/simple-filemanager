<?php

namespace SimpleFilemanager\Implementation\Silex;

use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;
use Silex\ServiceControllerResolver;
use Silex\ServiceProviderInterface;
use SimpleFilemanager\Implementation\Silex\Controllers\Filemanager;

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

        if (isset($app['twig'])) {
            $loader = new \Twig_Loader_Filesystem();
            $loader->addPath(__DIR__ . '/Resources/views', 'simple-filemanager');
            $app['twig']->getLoader()->addLoader($loader);
        }

        /** @var ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];
        $controllers->get('/', 'simple-filemanager.controller:listAction')->bind('simple-filemanager.overview');
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