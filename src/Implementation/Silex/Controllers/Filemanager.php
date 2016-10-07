<?php

namespace SimpleFilemanager\Implementation\Silex\Controllers;

use Silex\Application;

class Filemanager
{

    public function listAction(Application $app)
    {
        return $app['twig']->render('@simple-filemanager/list.twig');
    }

    public function uploadAction(Application $app, $mixed)
    {
        dump($mixed);
        return $app['twig']->render('@simple-filemanager/list.twig');
    }
}