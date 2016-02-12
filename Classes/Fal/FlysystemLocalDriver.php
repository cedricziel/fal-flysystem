<?php

namespace CedricZiel\FalFlysystem\Fal;

use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;

/**
 * Class FlysystemLocalDriver
 * Flysystem FAL Driver that uses the Local adapter.
 *
 * @package CedricZiel\FalFlysystem\Fal
 */
class FlysystemLocalDriver extends FlysystemDriver
{
    /**
     * Initializes this object. This is called by the storage after the driver
     * has been attached.
     *
     * @return void
     */
    public function initialize()
    {
        $this->adapter = new Local($this->entryPath);
        $this->filesystem = new Filesystem($this->adapter);
    }
}
