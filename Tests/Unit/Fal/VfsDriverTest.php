<?php

namespace CedricZiel\FalFlysystem\Tests\Unit\Fal;

use CedricZiel\FalFlysystem\Fal\VfsDriver;
use PHPUnit_Framework_TestCase;

/**
 * Class VfsDriverTest
 * Tests the abstract FlysystemDriver through the VfsDriver
 * which maps closest to the LocalDriver.
 *
 * @package CedricZiel\FalFlysystem\Tests\Unit\Fal
 */
class VfsDriverTest extends PHPUnit_Framework_TestCase
{
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
        $this->assertEquals($byteSize, mb_strlen($driver->getFileContents('test.txt'), '8bit'));
        $this->assertTrue($driver->getFilesystem()->has('test'));
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
        $this->assertTrue($driver->folderExists('/user_upload'));
    }

    /**
     * @return VfsDriver
     */
    private function getInitializedDriver()
    {
        $driver = new VfsDriver(['path' => '/']);
        $driver->initialize();

        return $driver;
    }
}
