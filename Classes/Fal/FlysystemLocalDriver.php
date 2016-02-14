<?php

namespace CedricZiel\FalFlysystem\Fal;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2016 Cedric Ziel <cedric@cedric-ziel.com>
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

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
        $this->adapter = new Local($this->entryPath, LOCK_EX, Local::SKIP_LINKS);
        $this->filesystem = new Filesystem($this->adapter);
    }

    /**
     * Processes the configuration for this driver.
     * @return void
     */
    public function processConfiguration()
    {
        parent::processConfiguration();
        $this->entryPath = $this->configuration['path'];
    }
}
