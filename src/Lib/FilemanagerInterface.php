<?php

namespace SimpleFilemanager\Lib;


interface FilemanagerInterface
{
    public function rename($originalPath, $newPath, $overwrite = false);

    public function copy($originalPath, $newPath);

    /**
     * @param $path
     */
    public function remove($path);

    /**
     * @param $path
     * @return mixed
     */
    public function mkdir($path);

    /**
     * @param $path
     * @param $content
     */
    public function upload($path, $content);
}