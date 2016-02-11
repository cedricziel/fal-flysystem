<?php
$EM_CONF[$_EXTKEY] = array(
    'title' => 'flysystem',
    'description' => 'File Abstraction Layer driver for TYPO3 CMS that uses Flysystem',
    'category' => 'be',
    'author' => 'Cedric Ziel',
    'author_email' => 'cedric@cedric-ziel.com',
    'author_company' => '',
    'state' => 'beta',
    'uploadfolder' => '0',
    'createDirs' => '',
    'clearCacheOnLoad' => 0,
    'version' => '0.1.0-dev',
    'constraints' => array(
        'depends' => array(
            'extbase' => '7.6.0-7.9.99',
            'typo3' => '7.6.0-7.9.99',
        ),
        'conflicts' => array(),
        'suggests' => array(),
    ),
);
