<?php

namespace SimpleFilemanager\Implementation\Silex\Controllers;

use Silex\Application;

class Filemanager
{

    public function listAction(Application $app)
    {
        return $app['twig']->render('list.twig');
    }
}