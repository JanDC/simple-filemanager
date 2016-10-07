<?php


namespace SimpleFilemanager\Lib;

use Symfony\Component\Filesystem\Filesystem;

class SimpleFilemanager extends Filesystem implements FilemanagerInterface
{
    /**
     * @param $path
     * @param $content
     */
    public function upload($path, $content)
    {
        $this->dumpFile($path, $content);
    }
}