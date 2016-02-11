<?php

namespace CedricZiel\FalFlysystem\Fal;

use League\Flysystem\Adapter\Local;

/**
 * Class FlysystemLocalDriver
 * @package CedricZiel\FalFlysystem\Fal
 */
class FlysystemLocalDriver extends FlysystemDriver
{
    public function initialize()
    {
        $path = $this->configuration['path'];
        $this->adapter = new Local($path);
    }
}
