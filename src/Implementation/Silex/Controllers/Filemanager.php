<?php

namespace SimpleFilemanager\Implementation\Silex\Controllers;

use Silex\Application;
use SimpleFilemanager\Lib\SimpleFilemanager;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Filemanager
{

    /**
     * @param Application $app
     * @param string|null $directory
     *
     * @return string
     */
    public function listAction(Application $app, $directory = null)
    {
        /** @var SimpleFilemanager $sfm */
        $sfm = $app['simple-filemanager.service'];
        $listing = is_null($directory) ? $sfm->listRoot() : $sfm->listDirectoryEntries($directory);
        return $app['twig']->render('@simple-filemanager/list.twig', ['listing' => $listing, 'directory' => is_null($directory) ? DIRECTORY_SEPARATOR : $directory]);
    }

    /**
     * @param Application $app
     * @param string|null $directory
     *
     * @return RedirectResponse
     */
    public function uploadAction(Application $app, $directory = null)
    {
        /** @var SimpleFilemanager $sfm */
        $sfm = $app['simple-filemanager.service'];

        /** @var Request $request */
        $request = $app['request'];

        /** @var UploadedFile $uploadedFile */
        foreach ($request->files->get('upload-field') as $uploadedFile) {
            $sfm->copyToDirectory($uploadedFile, $directory);
        }
        return new RedirectResponse($app['url_generator']->generate('simple-filemanager.overview', ['directory' => $directory]));
    }

    /**
     * @param Application $app
     * @param string $path
     *
     * @return Response
     */
    public function openAction(Application $app, $path)
    {
        /** @var SimpleFilemanager $sfm */
        $sfm = $app['simple-filemanager.service'];
        $fullPath = $sfm->buildFullPath($path);
        $file = $sfm->open($fullPath);
        return new Response($file->getContents(), 200, ['Content-Type' => $sfm->getMimeTypeByFile($file)]);
    }

    /**
     * @param Application $app
     * @param string $type
     * @param string $path
     *
     * @return Response
     */
    public function operationAction(Application $app, $type, $path)
    {
        /**@var Request $request */
        $request = $app['request'];

        /** @var SimpleFilemanager $sfm */
        $sfm = $app['simple-filemanager.service'];
        $fullPath = $sfm->buildFullPath($path);

        if (!$sfm->isFile($fullPath)){
            $this->handeDirectoryOperation($type,$path,$request->get('newname-field',false));
            return new RedirectResponse($app['url_generator']->generate('simple-filemanager.overview', ['directory' => $fullPath]));

        }

        $file = $sfm->open($fullPath);


        switch ($type) {
            case 'rename':
                $sfm->rename($file->getRealPath(), $file->getPath() . DIRECTORY_SEPARATOR . $request->get('newname-field'));
                break;
            case 'duplicate':
                $sfm->copy($file->getRealPath(), $file->getPath() . DIRECTORY_SEPARATOR . $request->get('newname-field'));
                break;
            case 'delete':
                $sfm->remove($file->getPath());
                break;
        }

        return new RedirectResponse($app['url_generator']->generate('simple-filemanager.overview', ['directory' => $file->getRelativePath()]));
    }

    private function handeDirectoryOperation($type, $path, $get)
    {
        // Not yet supported!!
    }
}