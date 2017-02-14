<?php

namespace SimpleFilemanager\Lib;

use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\Iterator\SortableIterator;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class SimpleFilemanager extends Filesystem implements FilemanagerInterface
{
    /** @var string */
    private $rootDirectory;

    /**
     * @param string $rootDirectory
     */
    public function __construct(string $rootDirectory)
    {
        $this->rootDirectory = $rootDirectory;
    }

    /**
     * @return array[Iterator,Iterator]
     */
    public function listRoot()
    {
        return [
            'directories' => Finder::create()->in($this->rootDirectory)->ignoreDotFiles(false)->depth(0)->directories()->sortByName()->getIterator(),
            'files' => Finder::create()->in($this->rootDirectory)->ignoreDotFiles(false)->depth(0)->files()->sortByName()->getIterator(),
        ];

    }

    public function createDirectoryPath($path)
    {
        $relativePath = $this->getPathRelativeToRoot($path);

        $tokens = array_filter(explode(DIRECTORY_SEPARATOR, $relativePath));

        $paths = [['path' => '', 'name' => '/']];
        foreach ($tokens as $key => $token) {
            $paths[] = ['path' => $paths[$key]['path'] . $token . DIRECTORY_SEPARATOR, 'name' => $token];
        }

        return $paths;
    }

    /**
     * @param $directoryPath
     *
     * @return array[Iterator,Iterator]
     */
    public function listDirectoryEntries($directoryPath)
    {
        return [
            'directories' => Finder::create()->in("{$this->rootDirectory}/{$directoryPath}")->ignoreDotFiles(false)->depth(0)->directories()->sortByName()->getIterator(),
            'files' => Finder::create()->in("{$this->rootDirectory}/{$directoryPath}")->ignoreDotFiles(false)->depth(0)->files()->sortByName()->getIterator(),
        ];
    }

    /**
     * @param string $path
     * @param bool $allowDirectories
     *
     * @return SplFileInfo
     */
    public function open(string $path, $allowDirectories = false)
    {
        if (!$this->exists($path)) {
            throw new FileNotFoundException("'{$path}' does not exist.");
        }

        if (!($this->isFile($path) || $allowDirectories)) {
            throw new FileNotFoundException("'{$path}' is not a file.");
        }

        $filename = substr($path, strrpos($path, DIRECTORY_SEPARATOR, -1));
        $fileinfo = new SplFileInfo($path, '', $filename);
        return $fileinfo;
    }

    /**
     * @param $path
     * @param $content
     */
    public function upload($path, $content)
    {
        $this->dumpFile($path, $content);
    }

    /**
     * @param string $path
     *
     * @return bool
     */
    public function isFile(string $path)
    {
        return !(is_link($path) || is_dir($path)) && is_file($path);
    }

    public function getMimeTypeByFile(SplFileInfo $file)
    {
        return mime_content_type($file->getPath());
    }

    /**
     * @param string $path
     *
     * @return string
     */
    public function buildFullPath($path)
    {
        return $this->rootDirectory . DIRECTORY_SEPARATOR . $path;
    }

    /**
     * @param UploadedFile $file
     *
     * @param string|null $directory
     *
     * @return string The resulting path
     */
    public function copyToDirectory(UploadedFile $file, $directory = null)
    {
        $fullDirectory = $this->buildFullPath($directory);
        if (!$this->exists($fullDirectory)) {
            $this->mkdir($fullDirectory);
        }
        $this->copy($file->getRealPath(), $fullDirectory . DIRECTORY_SEPARATOR . $file->getClientOriginalName());

        return $directory . DIRECTORY_SEPARATOR . $file->getClientOriginalName();
    }

    /**
     * @param string $path
     *
     * @return string
     */
    public function getParentDirectory($path)
    {
        try {
            $directory = $this->open($this->exists($path) ? $path : $this->buildFullPath($path), true)->getPath();
        } catch (FileNotFoundException $fnfe) {
            $directory = substr($this->buildFullPath($path), 0, strrpos($this->buildFullPath($path), DIRECTORY_SEPARATOR, -1));
        }

        return $this->getPathRelativeToRoot($directory);
    }

    /**
     * @param $path
     *
     * @return string
     */
    public function getPathRelativeToRoot($path)
    {
        return ltrim(str_replace($this->rootDirectory, '', $path), DIRECTORY_SEPARATOR);
    }
}