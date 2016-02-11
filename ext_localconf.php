<?php

$GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['registeredDrivers']['flysystem'] = array(
    'class' => CedricZiel\FalFlysystem\Fal\FlysystemDriver::class,
    'flexFormDS' => 'FILE:EXT:fal_flysystem/Configuration/FlexForm/FlysystemDriver.xml',
    'label' => 'Flysystem Driver',
    'shortName' => 'Flysystem',
);

$GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['registeredDrivers']['flysystem_local'] = array(
    'class' => CedricZiel\FalFlysystem\Fal\FlysystemLocalDriver::class,
    'flexFormDS' => 'FILE:EXT:fal_flysystem/Configuration/FlexForm/FlysystemLocalDriver.xml',
    'label' => 'Flysystem Local Driver',
    'shortName' => 'FlysystemLocal',
);
