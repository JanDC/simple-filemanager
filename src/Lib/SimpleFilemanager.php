<?php

namespace SimpleFilemanager\Lib;

use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
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
        $this->rootDirectory = realpath($rootDirectory);
    }

    /**
     * @return array[Iterator,Iterator]
     */
    public function listRoot(): array
    {
        return [
            'directories' => Finder::create()->in($this->rootDirectory)->ignoreDotFiles(false)->depth(0)->directories()->sortByName()->getIterator(),
            'files' => Finder::create()->in($this->rootDirectory)->ignoreDotFiles(false)->depth(0)->files()->sortByName()->getIterator(),
        ];

    }

    /**
     * @param string $search
     * @param array  $extensions
     *
     * @return mixed
     */
    public function search(string $search, array $extensions = [])
    {
        $search = preg_replace('/[^a-zA-Z0-9\-_\.]/', '', $search);
        $searchRegex = str_replace(['-', '.'], ['\-', '\.'], $search);
        $extensionsRegex = implode('|', $extensions);

        return Finder::create()->files()->in($this->rootDirectory)->name('/('.$searchRegex.').*'.($extensions ? '\.('.$extensionsRegex.')' : '').'$/i')->sortByName();
    }

    public function createDirectoryPath($path)
    {
        $relativePath = $this->getPathRelativeToRoot($path);

        $tokens = array_filter(explode(DIRECTORY_SEPARATOR, $relativePath));

        $paths = [['path' => '', 'name' => '/']];
        foreach ($tokens as $key => $token) {
            $paths[] = ['path' => $paths[$key]['path'].$token.DIRECTORY_SEPARATOR, 'name' => $token];
        }

        return $paths;
    }

    /**
     * @param $directoryPath
     *
     * @return array[Iterator,Iterator]
     */
    public function listDirectoryEntries($directoryPath): array
    {
        return [
            'directories' => Finder::create()->in("{$this->rootDirectory}/{$directoryPath}")->ignoreDotFiles(false)->depth(0)->directories()->sortByName()->getIterator(),
            'files' => Finder::create()->in("{$this->rootDirectory}/{$directoryPath}")->ignoreDotFiles(false)->depth(0)->files()->sortByName()->getIterator(),
        ];
    }

    /**
     * @param string $path
     * @param bool   $allowDirectories
     *
     * @return SplFileInfo
     */
    public function open(string $path, $allowDirectories = false): SplFileInfo
    {
        if (!$this->exists($path)) {
            throw new FileNotFoundException("'{$path}' does not exist.");
        }

        if (!($this->isFile($path) || $allowDirectories)) {
            throw new FileNotFoundException("'{$path}' is not a file.");
        }

        $filename = substr($path, strrpos($path, DIRECTORY_SEPARATOR, -1));

        return new SplFileInfo($path, '', $filename);
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
    public function isFile(string $path): bool
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
    public function buildFullPath($path): string
    {
        return $this->rootDirectory.DIRECTORY_SEPARATOR.$path;
    }

    /**
     * @param UploadedFile $file
     *
     * @param string|null  $directory
     *
     * @return string The resulting path
     */
    public function copyToDirectory(UploadedFile $file, $directory = null): string
    {
        $fullDirectory = $this->buildFullPath($directory);
        if (!$this->exists($fullDirectory)) {
            $this->mkdir($fullDirectory);
        }
        $filename = $this->generateTargetFile($fullDirectory, pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME), $file->getClientOriginalExtension());

        $targetFile = $fullDirectory.DIRECTORY_SEPARATOR.$filename.'.'.$file->getClientOriginalExtension();

        $this->copy($file->getRealPath(), $targetFile);

        return $directory.DIRECTORY_SEPARATOR.$filename.'.'.$file->getClientOriginalExtension();
    }

    /**
     * @param $targetDirectory
     * @param $targetFilename
     * @param $originalExtension
     *
     * @return string
     */
    public function generateTargetFile($targetDirectory, $targetFilename, $originalExtension)
    {
        $targetFile = $targetDirectory.DIRECTORY_SEPARATOR.$targetFilename.'.'.$originalExtension;
        if ($this->exists($targetFile)) {
            $targetFilename .= '-copy';
            $targetFile = $targetDirectory.DIRECTORY_SEPARATOR.$targetFilename.'.'.$originalExtension;

            if ($this->exists($targetFile)) {
                return $this->generateTargetFile($targetDirectory, $targetFilename, $originalExtension);
            }
        }

        return $targetFilename;
    }

    /**
     * @param string $path
     *
     * @return string
     */
    public function getParentDirectory($path): string
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
    public function getPathRelativeToRoot($path): string
    {
        return ltrim(str_replace($this->rootDirectory, '', $path), DIRECTORY_SEPARATOR);
    }
}