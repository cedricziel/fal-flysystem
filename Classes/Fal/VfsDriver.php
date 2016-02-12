<?php

namespace CedricZiel\FalFlysystem\Fal;

use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemInterface;
use League\Flysystem\Vfs\VfsAdapter;
use VirtualFileSystem\FileSystem as Vfs;

/**
 * Class VfsDriver
 * Flysystem Driver that uses the Vfs adapter.
 *
 * @package CedricZiel\FalFlysystem\Fal
 */
class VfsDriver extends FlysystemDriver
{
    /**
     * Initializes this object. This is called by the storage after the driver
     * has been attached.
     *
     * @return void
     */
    public function initialize()
    {
        $this->adapter = new VfsAdapter(new Vfs);
        $this->filesystem = new Filesystem($this->adapter);
    }

    /**
     * For testing purposes.
     *
     * Returns the underlying filesystem instance to be able to
     * manipulate the content.
     *
     * @return FilesystemInterface
     */
    public function getFilesystem()
    {
        return $this->filesystem;
    }
}
