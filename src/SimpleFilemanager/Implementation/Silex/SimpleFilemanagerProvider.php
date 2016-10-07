<?php

namespace SimpleFilemanager\Implementation\Silex;

use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;
use Silex\ServiceControllerResolver;
use Silex\ServiceProviderInterface;
use SimpleFilemanager\Implementation\Silex\Controllers\Filemanager;

class SimpleFilemanagerProvider implements ServiceProviderInterface, ControllerProviderInterface
{

    /**
     * Registers services on the given container.
     *
     * This method should only be used to configure services and parameters.
     * It should not get services.
     *
     * @param Application $app container instance
     */
    public function register(Application $app)
    {
        $app['simple-filemanager.controller'] = $app->share(function($app) {
            return new Filemanager();
        });

        if (isset($app['twig'])) {
            $app['twig']->getLoader()->addLoader(new \Twig_Loader_Filesystem([__DIR__ . '/Resources/views']));
        }
    }

    /**
     * Returns routes to connect to the given application.
     *
     * @param Application $app An Application instance
     *
     * @return ControllerCollection A ControllerCollection instance
     */
    public function connect(Application $app)
    {
        if (!$app['resolver'] instanceof ServiceControllerResolver) {
            // using RuntimeException crashes PHP?!
            throw new \LogicException('You must enable the ServiceController service provider to be able to use these routes.');
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