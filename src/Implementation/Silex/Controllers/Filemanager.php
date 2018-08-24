<?php

namespace SimpleFilemanager\Implementation\Silex\Controllers;

use Psr\Log\InvalidArgumentException;
use Silex\Application;
use SimpleFilemanager\Lib\SimpleFilemanager;
use Symfony\Component\Finder\Exception\OperationNotPermitedException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;

class Filemanager
{

    /** @var SimpleFilemanager */
    private $sfm;

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
     * @param string $search
     * @param array $extensions
     *
     * @return mixed
     */
    public function search(string $search, array $extensions = [])
    {
        $search = preg_replace('/[^a-zA-Z0-9\-_\.]/', '', $search);
        $searchRegex = str_replace(['-', '.'], ['\-', '\.'], $search);

        $extensionsRegex = implode('|', $extensions);

        return Finder::create()->files()->in($this->rootDirectory)->name('/('.$searchRegex.').*' . ($extensions ? '\.(' . $extensionsRegex . ')' : '') . '$/i')->sortByName();
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
        /** @var Request $request */
        $request = $app['request'];

        $this->sfm = $app['simple-filemanager.service'];
        $fullPath = $this->sfm->buildFullPath($path);

        $newName = Slugify::create()->slugify($request->get('newname-field', false));

        if (!$this->sfm->isFile($fullPath)) {
            $returnDirectory = $this->handleDirectoryOperation($type, $fullPath, $newName);

            return new RedirectResponse($app['url_generator']->generate('files',
                ['directory' => ltrim($returnDirectory, DIRECTORY_SEPARATOR)]
            ));
        }

        $file = $this->sfm->open($fullPath);

        switch ($type) {
            case 'rename':
                $this->sfm->rename($file->getRealPath(), $file->getPath() . DIRECTORY_SEPARATOR . $request->get('newname-field'));
                break;
            case 'duplicate':
                $this->sfm->copy($file->getRealPath(), $file->getPath() . DIRECTORY_SEPARATOR . $request->get('newname-field'));
                break;
            case 'delete':
                $this->sfm->remove($file->getRealPath());
                break;
        }

        /** @var FlashBagInterface $flashBag */
        $flashBag = $request->getSession()->getFlashBag();

        $flashBag->add('message',
            sprintf($app['translator']->translate("fileoperation.$type"), $file->getFilename(), $request->get('newname-field'))
        );

        return new RedirectResponse($app['url_generator']->generate('simple-filemanager.overview', ['directory' => $file->getRelativePath()]));
    }


    /**
     * @param $type
     * @param $path
     * @param $newName
     *
     * @return string
     */
    private function handleDirectoryOperation($type, $path, $newName)
    {
        switch ($type) {
            case 'rename':
                $this->sfm->rename($path, $this->sfm->getParentDirectory($path) . DIRECTORY_SEPARATOR . $newName);

                return $this->sfm->getParentDirectory($path);
                break;
            case 'create-dir':
                $this->sfm->mkdir($path . DIRECTORY_SEPARATOR . $newName);

                return $this->sfm->getPathRelativeToRoot($path);
                break;
            case 'delete':
                $this->sfm->remove($path);

                return $this->sfm->getParentDirectory($path);
                break;
        }

        throw new InvalidArgumentException("The \"{$type}\" operation is not implemented.");
}
}