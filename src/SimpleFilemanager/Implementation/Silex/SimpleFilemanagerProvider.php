<?php

namespace SimpleFilemanager\Implementation\Silex;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Silex\Api\ControllerProviderInterface;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ServiceControllerResolver;
use SimpleFilemanager\Implementation\Silex\Controllers\Filemanager;

class SimpleFilemanagerProvider implements ServiceProviderInterface, ControllerProviderInterface
{

    /**
     * Registers services on the given container.
     *
     * This method should only be used to configure services and parameters.
     * It should not get services.
     *
     * @param Container $pimple A container instance
     */
    public function register(Container $pimple)
    {
        $app['simple-filemanager.controller'] = function ($app) {
            return new Filemanager();
        };

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
}