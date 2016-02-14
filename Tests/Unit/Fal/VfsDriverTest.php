<?php

namespace CedricZiel\FalFlysystem\Tests\Unit\Fal;

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

use CedricZiel\FalFlysystem\Fal\VfsDriver;
use CedricZiel\FalFlysystem\Tests\Unit\AbstractFlysystemDrivertest;
use PHPUnit_Framework_TestCase;
use TYPO3\CMS\Core\Resource\Exception\ExistingTargetFileNameException;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * Class VfsDriverTest
 * Tests the abstract FlysystemDriver through the VfsDriver
 * which maps closest to the LocalDriver.
 *
 * @package CedricZiel\FalFlysystem\Tests\Unit\Fal
 */
class VfsDriverTest extends AbstractFlysystemDrivertest
{
    protected $driver;

    public function setUp()
    {
        if (!defined('PATH_site')) {
            define('PATH_site', dirname(__FILE__));
        }
    }

    /**
     * @test
     */
    public function itCanBeInstantiated()
    {
        $driver = new VfsDriver(['path' => '/']);
        $driver->initialize();

        $this->assertInstanceOf(VfsDriver::class, $driver);
    }

    /**
     * @test
     */
    public function itCanCheckIfAFileExists()
    {
        $driver = $this->getInitializedDriver();
        $this->assertFalse($driver->fileExists('/foo.txt'));

        $driver->getFilesystem()->put('/foo.txt', 'bar');
        $this->assertTrue($driver->fileExists('/foo.txt'));

        $driver->getFilesystem()->put('/bar/foo.txt', 'bar');
        $this->assertTrue($driver->fileExists('/bar/foo.txt'));

        // make sure fileExists fails on folders
        $this->assertFalse($driver->fileExists('/bar/'));
        $this->assertFalse($driver->fileExists('/bar'));
    }

    /**
     * @test
     */
    public function itCanListDirectoriesInTheRoot()
    {
        $driver = $this->getInitializedDriver();

        $emptyFileArray = $driver->getFoldersInFolder('/');
        $this->assertTrue(is_array($emptyFileArray));
        $this->assertCount(0, $emptyFileArray);

        $driver->getFilesystem()->put('/foo/bar.txt', 'baz');
        $oneDirArray = $driver->getFoldersInFolder('/');
        $this->assertTrue(is_array($oneDirArray));
        $this->assertCount(1, $oneDirArray);
    }

    /**
     * @test
     */
    public function itCanListFilesInTheRoot()
    {
        $driver = $this->getInitializedDriver();

        $emptyFileArray = $driver->getFilesInFolder('/');
        $this->assertTrue(is_array($emptyFileArray));
        $this->assertCount(0, $emptyFileArray);

        $driver->getFilesystem()->put('yo.txt', 'whazzup?');
        $oneFileArray = $driver->getFilesInFolder('/');
        $this->assertTrue(is_array($oneFileArray));
        $this->assertCount(1, $oneFileArray);

        $driver->getFilesystem()->put('yo1.txt', 'whazzup?');
        $twoFileArray = $driver->getFilesInFolder('/');
        $this->assertTrue(is_array($twoFileArray));
        $this->assertCount(2, $twoFileArray);
    }

    /**
     * @test
     */
    public function itCanCreateFolders()
    {
        $driver = $this->getInitializedDriver();

        $this->assertFalse($driver->folderExists('/test'));
        $driver->createFolder('/test');
        $this->assertTrue($driver->folderExists('/test'));

        $this->assertFalse($driver->folderExists('/test/test2'));
        $driver->createFolder('test2', '/test');
        $this->assertTrue($driver->folderExists('/test/test2'));

        $driver->getFilesystem()->put('/biz/baz.txt', 'test');
        $this->assertFalse($driver->folderExists('/biz/baz.txt'));

        $driver->getFilesystem()->put('/bazzy/biz/baz.txt', 'test');
        $this->assertTrue($driver->folderExistsInFolder('/biz/', 'bazzy'));
        $this->assertFalse($driver->folderExistsInFolder('/biz/bazzy/', 'baz.txt'));

        $driver->createFolder('test3', '/test/test2/', true);
        $this->assertTrue($driver->folderExists('/test/test2/test3'));

        $driver->createFolder('_processed_');
        $this->assertTrue($driver->folderExists('/_processed_/'));
    }

    /**
     * @test
     */
    public function itCanRenameFolders()
    {
        $driver = $this->getInitializedDriver();

        $driver->createFolder('test');
        $this->assertTrue($driver->getFilesystem()->has('test'));

        $driver->renameFolder('test', 'test2');
        $this->assertTrue($driver->getFilesystem()->has('test2'));
    }

    /**
     * @test
     */
    public function itCanSetFileContents()
    {
        $driver = $this->getInitializedDriver();

        $byteSize = $driver->setFileContents('test.txt', 'test');
        $this->assertEquals($byteSize, $driver->getFilesystem()->getSize('test.txt'));
        $this->assertTrue($driver->getFilesystem()->has('test.txt'));
        $this->assertEquals('test', $driver->getFileContents('test.txt'));
    }

    /**
     * @test
     */
    public function itCanDetermineIfAFolderIsEmpty()
    {
        $driver = $this->getInitializedDriver();

        $this->assertTrue($driver->isFolderEmpty('/'));
    }

    /**
     * The default folder is used for uploads and save operations.
     * As thus it should created automatically on retrieval.
     *
     * @test
     */
    public function itCanCreateADefaultFolderAutomatically()
    {
        $driver = $this->getInitializedDriver();

        $this->assertFalse($driver->folderExists('/user_upload'));
        $defaultFolder = $driver->getDefaultFolder();
        $this->assertEquals('/user_upload/', $defaultFolder);
        $this->assertTrue($driver->folderExists('user_upload'));
    }

    /**
     * @test
     */
    public function itCanAddFilesToTheStorage()
    {
        $driver = $this->getInitializedDriver();

        $driver->addFile(__FILE__, '/', '', false);
        $this->assertTrue($driver->getFilesystem()->has(basename(__FILE__)));
    }

    /**
     * @test
     */
    public function itCanCountFoldersInFolder()
    {
        $driver = $this->getInitializedDriver();

        $this->assertEquals(0, $driver->countFoldersInFolder('/'));
        $driver->getFilesystem()->createDir('foo');
        $driver->getFilesystem()->createDir('bar');
        $this->assertEquals(2, $driver->countFoldersInFolder('/'));
    }

    /**
     * @test
     */
    public function itCanCheckIfAnIdentifierIsWithinAContainer()
    {
        $driver = $this->getInitializedDriver();

        $this->assertTrue($driver->isWithin('/', '/'));
        $this->assertTrue($driver->isWithin('/test', '/test'));
        $this->assertTrue($driver->isWithin('/test', '/test/bar.txt'));
        $this->assertTrue($driver->isWithin('/test/', '/test/bar.txt'));
        $this->assertFalse($driver->isWithin('/test/', '/baz/bar.txt'));
    }

    /**
     * @test
     */
    public function itCanCheckIfAFileExistsInAFolder()
    {
        $driver = $this->getInitializedDriver();

        $driver->getFilesystem()->put('/test/foo.txt', 'test');
        $this->assertTrue($driver->fileExistsInFolder('foo.txt', '/test'));
    }

    /**
     * @test
     */
    public function itCanGetFolderInfoByIdentifier()
    {
        $driver = $this->getInitializedDriver();

        $driver->getFilesystem()->createDir('test');
        $folderInfo = $driver->getFolderInfoByIdentifier('/test/');
        $expectedFolderInfo = [
            'name' => 'test',
            'identifier' => '/test/',
            'storage' => 33
        ];

        $this->assertEquals($expectedFolderInfo, $folderInfo);
    }

    /**
     * @test
     */
    public function itCanGetFileInfoByIdentifier()
    {
        $driver = $this->getInitializedDriver();

        $driver->getFilesystem()->put('bar/foo.txt', 'bar');
        $driver->getFilesystem()->put('foo.txt', 'bar');
        $file1InfoFromDriver = $driver->getFileInfoByIdentifier('/bar/foo.txt');
        $expectedFile1Info = [
            'mimetype' => 'application/octet-stream',
            'name' => 'foo.txt',
            'size' => 3,
            'identifier' => '/foo.txt',
            'identifier_hash' => 'b1b3f2846530a0515662476cc7030...31ee99',
            'storage' => 33,
            'folder_hash' => '42099b4af021e53fd8fd4e056c256...e3ffa8',
        ];
        $this->assertEquals($expectedFile1Info['mimetype'], $file1InfoFromDriver['mimetype']);
        $this->assertEquals($expectedFile1Info['name'], $file1InfoFromDriver['name']);
        $this->assertEquals($expectedFile1Info['size'], $file1InfoFromDriver['size']);
        $this->assertEquals($expectedFile1Info['storage'], $file1InfoFromDriver['storage']);
        $this->assertTrue(array_key_exists('folder_hash', $file1InfoFromDriver));
        $this->assertTrue(array_key_exists('identifier_hash', $file1InfoFromDriver));

        $file2InfoFromDriver = $driver->getFileInfoByIdentifier('/foo.txt');
        $expectedFile2Info = [
            'mimetype' => 'application/octet-stream',
            'name' => 'foo.txt',
            'size' => 3,
            'identifier' => '/foo.txt',
            'identifier_hash' => 'b1b3f2846530a0515662476cc7030...31ee99',
            'storage' => 33,
            'folder_hash' => '42099b4af021e53fd8fd4e056c256...e3ffa8',
        ];

        $this->assertEquals($expectedFile2Info['mimetype'], $file1InfoFromDriver['mimetype']);
        $this->assertEquals($expectedFile2Info['name'], $file1InfoFromDriver['name']);
        $this->assertEquals($expectedFile2Info['size'], $file1InfoFromDriver['size']);
        $this->assertEquals($expectedFile2Info['storage'], $file1InfoFromDriver['storage']);
        $this->assertTrue(array_key_exists('folder_hash', $file2InfoFromDriver));
        $this->assertTrue(array_key_exists('identifier_hash', $file2InfoFromDriver));
    }

    /**
     * @test
     */
    public function itCanGetAfolderInsideAFolderIdentifier()
    {
        $driver = $this->getInitializedDriver();

        $this->assertEquals('/test/test/', $driver->getFolderInFolder('test', '/test'));
    }

    /**
     * @test
     */
    public function itCanDeleteFiles()
    {
        $this->driver = $driver = $this->getInitializedDriver();
        $driver->getFilesystem()->put('test/bar.txt', 'test');
        $this->assertTrue($driver->fileExists('/test/bar.txt'));

        $driver->deleteFile('/test/bar.txt');
        $this->assertFalse($driver->fileExists('/test/bar.txt'));
    }

    /**
     * @test
     */
    public function itCanDeleteFolders()
    {
        $driver = $this->getInitializedDriver();
        $driver->getFilesystem()->put('test/bar.txt', 'test');
        $this->assertTrue($driver->folderExists('/test/'));
        $driver->getFilesystem()->put('test2/bar.txt', 'test');
        $this->assertTrue($driver->folderExists('/test2/'));
        $driver->getFilesystem()->put('test3/bar.txt', 'test');
        $this->assertTrue($driver->folderExists('/test3/'));


        // $driver->deleteFolder('/test3');
        // $this->assertFalse($driver->folderExists('/test3/'));
        $this->markTestIncomplete('Implement deletion. Flysystem vfs doesnt support unlinking from the wrapper');
    }

    /**
     * @test
     */
    public function itCanCreateFiles()
    {
        $driver = $this->getInitializedDriver();
        $this->assertFalse($driver->fileExists('/test.txt'));
        $driver->createFile('test.txt', '/');
        $this->assertTrue($driver->fileExists('/test.txt'));
    }

    /**
     * @test
     */
    public function itCanHashFileInfosWithSha1()
    {
        $driver = $this->getInitializedDriver();

        $driver->getFilesystem()->put('test.txt', 'wtf');

        $hash1 = $driver->hash('/test.txt', 'sha1');
        $hash2 = $driver->hash('/test.txt', 'sha1');

        $this->assertEquals($hash1, $hash2);
    }

    /**
     * @test
     */
    public function itCanHashFileInfosWithMd5()
    {
        $driver = $this->getInitializedDriver();

        $driver->getFilesystem()->put('test.txt', 'wtf');

        $hash1 = $driver->hash('/test.txt', 'md5');
        $hash2 = $driver->hash('/test.txt', 'md5');

        $this->assertEquals($hash1, $hash2);
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function itWillThrowAnExceptionOnAnUnsupportedHashAlgo()
    {
        $driver = $this->getInitializedDriver();

        $driver->getFilesystem()->put('test.txt', 'wtf');
        $driver->hash('/test.txt', 'rot13');
    }

    /**
     * @test
     */
    public function itCanRenameFiles()
    {
        $driver = $this->getInitializedDriver();

        $driver->getFilesystem()->put('test.txt', 'wtf');
        $this->assertTrue($driver->fileExists('/test.txt'));
        $driver->renameFile('/test.txt', 'bar.txt');
        $this->assertTrue($driver->fileExists('/bar.txt'));
        $this->assertFalse($driver->fileExists('/test.txt'));
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Core\Resource\Exception\ExistingTargetFileNameException
     */
    public function itCanThrowsAnExceptionWhenRenamingFilesToExistingIdentifiers()
    {
        $driver = $this->getInitializedDriver();

        $driver->getFilesystem()->put('test.txt', 'wtf');
        $driver->getFilesystem()->put('bar.txt', 'wtf');
        $this->assertTrue($driver->fileExists('/test.txt'));
        $driver->renameFile('/test.txt', 'bar.txt');
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Core\Resource\Exception\FolderDoesNotExistException
     */
    public function itThrowsAnExceptionWhenTryingToTheInfoFromANonexistentFolder()
    {
        $driver = $this->getInitializedDriver();
        $driver->getFolderInfoByIdentifier('/test/biz/');
    }

    /**
     * @test
     */
    public function itCanGetAFileIdentifierInAFolder()
    {
        $driver = $this->getInitializedDriver();
        $this->assertEquals('/test.txt', $driver->getFileInFolder('test.txt', '/'));
        $this->assertEquals('/biZ/baz/text.txt', $driver->getFileInFolder('text.txt', '/biZ/baz/'));
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Core\Resource\Exception\FileDoesNotExistException
     */
    public function itWillThrowAnExceptionWhenGetttingFileInfoByIdentifierOnANonexistentFile()
    {
        $driver = $this->getInitializedDriver();
        $driver->getFileInfoByIdentifier('/buz/biz/dings.pdf');
    }

    /**
     * @test
     */
    public function itCanDumpFileContents()
    {
        $this->markTestIncomplete('Implement dump file contents');
    }

    /**
     * @test
     */
    public function itCanCopyFolderWithinStorage()
    {
        $adapter = $this->getInitializedDriver();
        $adapter->getFilesystem()->createDir('bar');
        $adapter->getFilesystem()->put('bar/test.txt', 'test');
        $adapter->getFilesystem()->createDir('bang/boom/bang');

        $this->assertTrue($adapter->folderExists('/bar'));
        $this->assertTrue($adapter->folderExists('/bang/boom/bang'));

        $adapter->copyFolderWithinStorage('/bar', '/bang/boom/bang', 'biz');

        $this->assertTrue($adapter->folderExists('/bar'), 'Copy, dont move');
        $this->assertTrue($adapter->folderExists('/bang/boom/bang/biz'), 'Folder was copied');
        $this->markTestIncomplete('Implement copying a file within the same storage');
    }

    /**
     * @test
     */
    public function itCanCopyFilesWithinStorage()
    {
        $adapter = $this->getInitializedDriver();
        $adapter->getFilesystem()->createDir('bar');
        $adapter->getFilesystem()->createDir('bang/boom/bang');

        $this->assertTrue($adapter->folderExists('/bar'));
        $this->assertTrue($adapter->folderExists('/bang/boom/bang'));

        $this->assertEquals('/bang/boom/bang/test.txt', $adapter->createFile('test.txt', '/bang/boom/bang/'));
        $this->assertTrue($adapter->fileExists('/bang/boom/bang/test.txt'));

        $adapter->copyFileWithinStorage('/bang/boom/bang/test.txt', '/bang/boom', 'test.txt');

        $this->assertTrue($adapter->fileExists('/bang/boom/bang/test.txt'), 'Copy, dont move');
        $this->assertTrue($adapter->fileExists('/bang/boom/test.txt'), 'File was copied');
        $this->markTestIncomplete('Implement recursive copying');
    }

    /**
     * @test
     */
    public function itCanMoveFolderWithinStorage()
    {
        $driver = $this->getInitializedDriver();
        $driver->getFilesystem()->createDir('bar');
        $driver->getFilesystem()->createDir('bang/boom/bang');

        $this->assertTrue($driver->folderExists('/bar'));
        $this->assertTrue($driver->folderExists('/bang/boom/bang'));

        $this->assertEquals('/bang/boom/bang/test.txt', $driver->createFile('test.txt', '/bang/boom/bang/'));

        // $driver->moveFolderWithinStorage('/bang/boom/bang', '/bang/boom', 'bing');
        // $this->assertFalse($driver->folderExists('/bang/boom/bang'));
        // $this->assertTrue($driver->folderExists('/bang/boom/bing'));

        $this->markTestIncomplete('Implement moving folders within the same storage');
    }

    /**
     * @test
     */
    public function itCanMoveFileWithinStorage()
    {
        $this->markTestIncomplete('Implement moving files within the same storage');
    }

    /**
     * @test
     */
    public function itCanReplaceFile()
    {
        $this->markTestIncomplete('Implement replacing files');
    }

    /**
     * @test
     */
    public function itCanCopyFileWithinStorage()
    {
        $this->markTestIncomplete('Implement copying files within the same storage');
    }

    /**
     * @test
     */
    public function itCanDetectTheCorrectMimeType()
    {
        $this->markTestIncomplete('Implement reading the mimetype');
    }

    /**
     * @return VfsDriver
     */
    private function getInitializedDriver()
    {
        $driver = new VfsDriver(['path' => '/']);
        $driver->initialize();
        $driver->setStorageUid(33);

        return $driver;
    }
}
