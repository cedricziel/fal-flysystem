<?php

namespace CedricZiel\FalFlysystem\Fal;

use League\Flysystem\Adapter\Local;
use League\Flysystem\AdapterInterface;
use League\Flysystem\Config;
use League\Flysystem\FilesystemInterface;
use TYPO3\CMS\Core\Resource\Driver\AbstractHierarchicalFilesystemDriver;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Type\File\FileInfo;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

/**
 * Class FlysystemDriver
 * @package CedricZiel\FalFlysystem\Fal
 */
abstract class FlysystemDriver extends AbstractHierarchicalFilesystemDriver
{
    /**
     * @var FilesystemInterface
     */
    protected $filesystem;

    /**
     * @var AdapterInterface
     */
    protected $adapter;

    /**
     * @var string
     */
    protected $entryPath;

    /**
     * FlysystemDriver constructor.
     * @param array $configuration
     */
    public function __construct(array $configuration = [])
    {
        parent::__construct($configuration);
        // The capabilities default of this driver. See CAPABILITY_* constants for possible values
        $this->capabilities =
            ResourceStorage::CAPABILITY_BROWSABLE
            | ResourceStorage::CAPABILITY_PUBLIC
            | ResourceStorage::CAPABILITY_WRITABLE;
    }

    /**
     * Processes the configuration for this driver.
     * @return void
     */
    public function processConfiguration()
    {
        $this->entryPath = $this->configuration['path'];
    }

    /**
     * Merges the capabilities merged by the user at the storage
     * configuration into the actual capabilities of the driver
     * and returns the result.
     *
     * @param int $capabilities
     * @return int
     */
    public function mergeConfigurationCapabilities($capabilities)
    {
        // TODO: Implement mergeConfigurationCapabilities() method.
    }

    /**
     * Returns the identifier of the root level folder of the storage.
     *
     * @return string
     */
    public function getRootLevelFolder()
    {
        return '/';
    }

    /**
     * Returns the identifier of the default folder new files should be put into.
     *
     * @return string
     */
    public function getDefaultFolder()
    {
        $identifier = '/user_upload/';
        $createFolder = !$this->folderExists($identifier);
        if (true === $createFolder) {
            $identifier = $this->createFolder('user_upload');
        }
        return $identifier;
    }

    /**
     * Checks if a folder exists.
     *
     * @param string $folderIdentifier
     * @return bool
     */
    public function folderExists($folderIdentifier)
    {
        if ('/' === $folderIdentifier) {
            return true;
        } else {
            return ($this->filesystem->has('/' . $folderIdentifier) && $this->filesystem->get('/' . $folderIdentifier)->isDir());
        }
    }

    /**
     * Creates a folder, within a parent folder.
     * If no parent folder is given, a root level folder will be created
     *
     * @param string $newFolderName
     * @param string $parentFolderIdentifier
     * @param bool $recursive
     * @return string the Identifier of the new folder
     */
    public function createFolder($newFolderName, $parentFolderIdentifier = '', $recursive = false)
    {
        $parentFolderIdentifier = $this->canonicalizeAndCheckFolderIdentifier($parentFolderIdentifier);
        $newFolderName = trim($newFolderName, '/');
        if (false === $recursive) {
            $newFolderName = $this->sanitizeFileName($newFolderName);
            $newIdentifier = $parentFolderIdentifier . $newFolderName . '/';
            $this->filesystem->createDir($newIdentifier);
        } else {
            $parts = GeneralUtility::trimExplode('/', $newFolderName);
            $parts = array_map(array($this, 'sanitizeFileName'), $parts);
            $newFolderName = implode('/', $parts);
            $newIdentifier = $parentFolderIdentifier . $newFolderName . '/';
        }
        return $newIdentifier;
    }

    /**
     * Returns the public URL to a file.
     * Either fully qualified URL or relative to PATH_site (rawurlencoded).
     *
     * @param string $identifier
     * @return string
     */
    public function getPublicUrl($identifier)
    {
        return '/';
    }

    /**
     * Renames a folder in this storage.
     *
     * @param string $folderIdentifier
     * @param string $newName
     * @return array A map of old to new file identifiers of all affected resources
     */
    public function renameFolder($folderIdentifier, $newName)
    {
        $renameResult = $this->filesystem->rename($folderIdentifier, $newName);

        if (true === $renameResult) {
            return [$folderIdentifier => $newName];
        } else {
            return [$folderIdentifier => $folderIdentifier];
        }
    }

    /**
     * Removes a folder in filesystem.
     *
     * @param string $folderIdentifier
     * @param bool $deleteRecursively
     * @return bool
     */
    public function deleteFolder($folderIdentifier, $deleteRecursively = false)
    {
        return $this->filesystem->deleteDir($folderIdentifier);
    }

    /**
     * Checks if a file exists.
     *
     * @param string $fileIdentifier
     * @return bool
     */
    public function fileExists($fileIdentifier)
    {
        if ($this->filesystem->has($fileIdentifier) && !$this->filesystem->get($fileIdentifier)->isDir()) {
            return true;
        }
        return false;
    }

    /**
     * Checks if a folder contains files and (if supported) other folders.
     *
     * @param string $folderIdentifier
     * @return bool TRUE if there are no files and folders within $folder
     */
    public function isFolderEmpty($folderIdentifier)
    {
        return 0 === count($this->filesystem->listContents($folderIdentifier));
    }

    /**
     * Adds a file from the local server hard disk to a given path in TYPO3s
     * virtual file system. This assumes that the local file exists, so no
     * further check is done here! After a successful the original file must
     * not exist anymore.
     *
     * @param string $localFilePath (within PATH_site)
     * @param string $targetFolderIdentifier
     * @param string $newFileName optional, if not given original name is used
     * @param bool $removeOriginal if set the original file will be removed
     *                                after successful operation
     * @return string the identifier of the new file
     */
    public function addFile($localFilePath, $targetFolderIdentifier, $newFileName = '', $removeOriginal = true)
    {
        $localFilePath = $this->canonicalizeAndCheckFilePath($localFilePath);
        $newFileName = $this->sanitizeFileName($newFileName !== '' ? $newFileName : PathUtility::basename($localFilePath));
        $newFileIdentifier = $this->canonicalizeAndCheckFolderIdentifier($targetFolderIdentifier) . $newFileName;

        $targetPath = ltrim($newFileIdentifier, '/');

        $content = file_get_contents($localFilePath);

        if ($removeOriginal) {
            if (is_uploaded_file($localFilePath)) {
                $result = $this->filesystem->put($targetPath, $content);
            } else {
                $result = rename($localFilePath, $targetPath);
            }
            unlink($localFilePath);
        } else {
            $result = $this->filesystem->put($targetPath, $content);
        }
        if ($result === false || !$this->filesystem->has($targetPath)) {
            throw new \RuntimeException('Adding file ' . $localFilePath . ' at ' . $newFileIdentifier . ' failed.');
        }
        clearstatcache();
        return $newFileIdentifier;
    }

    /**
     * Creates a new (empty) file and returns the identifier.
     *
     * @param string $fileName
     * @param string $parentFolderIdentifier
     * @return string
     */
    public function createFile($fileName, $parentFolderIdentifier)
    {
        // TODO: Implement createFile() method.
        DebuggerUtility::var_dump([
            '$fileName' => $fileName,
            '$parentFolderIdentifier' => $parentFolderIdentifier
        ], 'createFile');
    }

    /**
     * Copies a file *within* the current storage.
     * Note that this is only about an inner storage copy action,
     * where a file is just copied to another folder in the same storage.
     *
     * @param string $fileIdentifier
     * @param string $targetFolderIdentifier
     * @param string $fileName
     * @return string the Identifier of the new file
     */
    public function copyFileWithinStorage($fileIdentifier, $targetFolderIdentifier, $fileName)
    {
        // TODO: Implement copyFileWithinStorage() method.
        DebuggerUtility::var_dump([
            '$fileIdentifier' => $fileIdentifier,
            '$targetFolderIdentifier' => $targetFolderIdentifier,
            '$fileName' => $fileName
        ], 'copyFileWithinStorage');
    }

    /**
     * Renames a file in this storage.
     *
     * @param string $fileIdentifier
     * @param string $newName The target path (including the file name!)
     * @return string The identifier of the file after renaming
     */
    public function renameFile($fileIdentifier, $newName)
    {
        // TODO: Implement renameFile() method.
        DebuggerUtility::var_dump([
            '$fileIdentifier' => $fileIdentifier,
            '$newName' => $newName
        ], 'renameFile');
    }

    /**
     * Replaces a file with file in local file system.
     *
     * @param string $fileIdentifier
     * @param string $localFilePath
     * @return bool TRUE if the operation succeeded
     */
    public function replaceFile($fileIdentifier, $localFilePath)
    {
        // TODO: Implement replaceFile() method.
        DebuggerUtility::var_dump([
            '$fileIdentifier' => $fileIdentifier,
            '$localFilePath' => $localFilePath
        ], 'replaceFile');
    }

    /**
     * Removes a file from the filesystem. This does not check if the file is
     * still used or if it is a bad idea to delete it for some other reason
     * this has to be taken care of in the upper layers (e.g. the Storage)!
     *
     * @param string $fileIdentifier
     * @return bool TRUE if deleting the file succeeded
     */
    public function deleteFile($fileIdentifier)
    {
        return $this->filesystem->delete($fileIdentifier);
    }

    /**
     * Creates a hash for a file.
     *
     * @param string $fileIdentifier
     * @param string $hashAlgorithm The hash algorithm to use
     * @return string
     */
    public function hash($fileIdentifier, $hashAlgorithm)
    {
        // TODO: Implement hash() method.
    }

    /**
     * Moves a file *within* the current storage.
     * Note that this is only about an inner-storage move action,
     * where a file is just moved to another folder in the same storage.
     *
     * @param string $fileIdentifier
     * @param string $targetFolderIdentifier
     * @param string $newFileName
     * @return string
     */
    public function moveFileWithinStorage($fileIdentifier, $targetFolderIdentifier, $newFileName)
    {
        // TODO: Implement moveFileWithinStorage() method.
    }

    /**
     * Folder equivalent to moveFileWithinStorage().
     *
     * @param string $sourceFolderIdentifier
     * @param string $targetFolderIdentifier
     * @param string $newFolderName
     * @return array All files which are affected, map of old => new file identifiers
     */
    public function moveFolderWithinStorage($sourceFolderIdentifier, $targetFolderIdentifier, $newFolderName)
    {
        // TODO: Implement moveFolderWithinStorage() method.
    }

    /**
     * Folder equivalent to copyFileWithinStorage().
     *
     * @param string $sourceFolderIdentifier
     * @param string $targetFolderIdentifier
     * @param string $newFolderName
     * @return bool
     */
    public function copyFolderWithinStorage($sourceFolderIdentifier, $targetFolderIdentifier, $newFolderName)
    {
        // TODO: Implement copyFolderWithinStorage() method.
    }

    /**
     * Returns the contents of a file. Beware that this requires to load the
     * complete file into memory and also may require fetching the file from an
     * external location. So this might be an expensive operation (both in terms
     * of processing resources and money) for large files.
     *
     * @param string $fileIdentifier
     * @return string The file contents
     */
    public function getFileContents($fileIdentifier)
    {
        return $this->filesystem->read($fileIdentifier);
    }

    /**
     * Sets the contents of a file to the specified value.
     *
     * @param string $fileIdentifier
     * @param string $contents
     * @return int The number of bytes written to the file
     */
    public function setFileContents($fileIdentifier, $contents)
    {
        $this->filesystem->put($fileIdentifier, $contents);

        return $this->filesystem->getSize($fileIdentifier);
    }

    /**
     * Checks if a file inside a folder exists
     *
     * @param string $fileName
     * @param string $folderIdentifier
     * @return bool
     */
    public function fileExistsInFolder($fileName, $folderIdentifier)
    {
        $identifier = $folderIdentifier . '/' . $fileName;
        $identifier = $this->canonicalizeAndCheckFileIdentifier($identifier);
        return $this->fileExists($identifier);
    }

    /**
     * Checks if a folder inside a folder exists.
     *
     * @param string $folderName
     * @param string $folderIdentifier
     * @return bool
     */
    public function folderExistsInFolder($folderName, $folderIdentifier)
    {
        $identifier = $folderIdentifier . '/' . $folderName;
        $identifier = $this->canonicalizeAndCheckFolderIdentifier($identifier);
        return $this->folderExists($identifier);
    }

    /**
     * Returns a path to a local copy of a file for processing it. When changing the
     * file, you have to take care of replacing the current version yourself!
     *
     * @param string $fileIdentifier
     * @param bool $writable Set this to FALSE if you only need the file for read
     *                       operations. This might speed up things, e.g. by using
     *                       a cached local version. Never modify the file if you
     *                       have set this flag!
     * @return string The path to the file on the local disk
     */
    public function getFileForLocalProcessing($fileIdentifier, $writable = true)
    {
        // TODO: Implement getFileForLocalProcessing() method.
        DebuggerUtility::var_dump([
            '$fileIdentifier' => $fileIdentifier,
            '$writable' => $writable,
        ], 'getFileForLocalProcessing');
    }

    /**
     * Returns the permissions of a file/folder as an array
     * (keys r, w) of boolean flags
     *
     * @param string $identifier
     * @return array
     */
    public function getPermissions($identifier)
    {
        return array(
            'r' => true,
            'w' => true,
        );
    }

    /**
     * Directly output the contents of the file to the output
     * buffer. Should not take care of header files or flushing
     * buffer before. Will be taken care of by the Storage.
     *
     * @param string $identifier
     * @return void
     */
    public function dumpFileContents($identifier)
    {
        // TODO: Implement dumpFileContents() method.
        DebuggerUtility::var_dump([
            '$identifier' => $identifier,
        ], 'dumpFileContents');
    }

    /**
     * Checks if a given identifier is within a container, e.g. if
     * a file or folder is within another folder.
     * This can e.g. be used to check for web-mounts.
     *
     * Hint: this also needs to return TRUE if the given identifier
     * matches the container identifier to allow access to the root
     * folder of a filemount.
     *
     * @param string $folderIdentifier
     * @param string $identifier identifier to be checked against $folderIdentifier
     * @return bool TRUE if $content is within or matches $folderIdentifier
     */
    public function isWithin($folderIdentifier, $identifier)
    {
        $folderIdentifier = $this->canonicalizeAndCheckFileIdentifier($folderIdentifier);
        $entryIdentifier = $this->canonicalizeAndCheckFileIdentifier($identifier);
        if ($folderIdentifier === $entryIdentifier) {
            return true;
        }
        // File identifier canonicalization will not modify a single slash so
        // we must not append another slash in that case.
        if ($folderIdentifier !== '/') {
            $folderIdentifier .= '/';
        }
        return GeneralUtility::isFirstPartOfStr($entryIdentifier, $folderIdentifier);
    }

    /**
     * Returns information about a file.
     *
     * @param string $fileIdentifier
     * @param array $propertiesToExtract Array of properties which are be extracted
     *                                   If empty all will be extracted
     * @return array
     */
    public function getFileInfoByIdentifier($fileIdentifier, array $propertiesToExtract = [])
    {
        $relativeDriverPath = ltrim($fileIdentifier, '/');
        if (!$this->filesystem->has($relativeDriverPath) || !$this->filesystem->get($relativeDriverPath)->isFile()) {
            throw new \InvalidArgumentException('File ' . $fileIdentifier . ' does not exist.', 1314516809);
        }
        $dirPath = PathUtility::dirname($fileIdentifier);
        $dirPath = $this->canonicalizeAndCheckFolderIdentifier($dirPath);
        return $this->extractFileInformation($relativeDriverPath, $dirPath, $propertiesToExtract);
    }

    /**
     * Returns information about a file.
     *
     * @param string $folderIdentifier
     * @return array
     */
    public function getFolderInfoByIdentifier($folderIdentifier)
    {
        $folderIdentifier = $this->canonicalizeAndCheckFolderIdentifier($folderIdentifier);

        return [
            'identifier' => $folderIdentifier,
            'name' => PathUtility::basename($folderIdentifier),
            'storage' => $this->storageUid
        ];
    }

    /**
     * Returns the identifier of a file inside the folder
     *
     * @param string $fileName
     * @param string $folderIdentifier
     * @return string file identifier
     */
    public function getFileInFolder($fileName, $folderIdentifier)
    {
        return $this->canonicalizeAndCheckFileIdentifier($folderIdentifier . '/' . $fileName);
    }

    /**
     * Returns a list of files inside the specified path
     *
     * @param string $folderIdentifier
     * @param int $start
     * @param int $numberOfItems
     * @param bool $recursive
     * @param array $filenameFilterCallbacks callbacks for filtering the items
     * @param string $sort Property name used to sort the items.
     *                     Among them may be: '' (empty, no sorting), name,
     *                     fileext, size, tstamp and rw.
     *                     If a driver does not support the given property, it
     *                     should fall back to "name".
     * @param bool $sortRev TRUE to indicate reverse sorting (last to first)
     * @return array of FileIdentifiers
     */
    public function getFilesInFolder(
        $folderIdentifier,
        $start = 0,
        $numberOfItems = 0,
        $recursive = false,
        array $filenameFilterCallbacks = [],
        $sort = '',
        $sortRev = false
    ) {
        $calculatedFolderIdentifier = ltrim($this->canonicalizeAndCheckFolderIdentifier($folderIdentifier), '/');
        $contents = $this->filesystem->listContents($calculatedFolderIdentifier);
        $files = [];

        /*
         * Filter directories
         */
        foreach ($contents as $directoryItem) {
            if ('file' === $directoryItem['type']) {
                $files['/' . $directoryItem['path']] = '/' . $directoryItem['path'];
            }
        }

        return $files;
    }

    /**
     * Returns the identifier of a folder inside the folder
     *
     * @param string $folderName The name of the target folder
     * @param string $folderIdentifier
     * @return string folder identifier
     */
    public function getFolderInFolder($folderName, $folderIdentifier)
    {
        $folderIdentifier = $this->canonicalizeAndCheckFolderIdentifier($folderIdentifier . '/' . $folderName);
        return $folderIdentifier;
    }

    /**
     * Returns a list of folders inside the specified path
     *
     * @param string $folderIdentifier
     * @param int $start
     * @param int $numberOfItems
     * @param bool $recursive
     * @param array $folderNameFilterCallbacks callbacks for filtering the items
     * @param string $sort Property name used to sort the items.
     *                     Among them may be: '' (empty, no sorting), name,
     *                     fileext, size, tstamp and rw.
     *                     If a driver does not support the given property, it
     *                     should fall back to "name".
     * @param bool $sortRev TRUE to indicate reverse sorting (last to first)
     * @return array of Folder Identifier
     * @TODO: Implement pagination with $start and $numberOfItems
     * @TODO: Implement directory filter callbacks
     * @TODO: Implement sorting
     */
    public function getFoldersInFolder(
        $folderIdentifier,
        $start = 0,
        $numberOfItems = 0,
        $recursive = false,
        array $folderNameFilterCallbacks = [],
        $sort = '',
        $sortRev = false
    ) {
        $calculatedFolderIdentifier = ltrim($this->canonicalizeAndCheckFolderIdentifier($folderIdentifier), '/');
        $contents = $this->filesystem->listContents($calculatedFolderIdentifier);
        $directories = [];

        /*
         * Filter directories
         */
        foreach ($contents as $directoryItem) {
            if ('dir' === $directoryItem['type']) {
                $directories['/' . $directoryItem['path']]
                    = '/' . $directoryItem['path'];
            }
        }

        return $directories;
    }

    /**
     * Returns the number of files inside the specified path
     *
     * @param string $folderIdentifier
     * @param bool $recursive
     * @param array $filenameFilterCallbacks callbacks for filtering the items
     * @return int Number of files in folder
     * @TODO: Implement recursive count
     * @TODO: Implement filename filtering
     */
    public function countFilesInFolder($folderIdentifier, $recursive = false, array $filenameFilterCallbacks = [])
    {

        return count($this->getFilesInFolder($folderIdentifier, 0, 0, $recursive, $filenameFilterCallbacks));
    }

    /**
     * Returns the number of folders inside the specified path
     *
     * @param string $folderIdentifier
     * @param bool $recursive
     * @param array $folderNameFilterCallbacks callbacks for filtering the items
     * @return int Number of folders in folder
     */
    public function countFoldersInFolder($folderIdentifier, $recursive = false, array $folderNameFilterCallbacks = [])
    {
        $count = 0;
        $filesystemRelativeIdentifier = ltrim($folderIdentifier, '/');
        $directoryListing = $this->filesystem->listContents($filesystemRelativeIdentifier);
        foreach ($directoryListing as $entry) {
            if ('dir' === $entry['type']) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Extracts information about a file from the filesystem.
     *
     * @param string $filePath The absolute path to the file
     * @param string $containerPath The relative path to the file's container
     * @param array $propertiesToExtract array of properties which should be returned, if empty all will be extracted
     * @return array
     */
    protected function extractFileInformation($filePath, $containerPath, array $propertiesToExtract = array())
    {
        if (empty($propertiesToExtract)) {
            $propertiesToExtract = array(
                'size',
                'atime',
                'atime',
                'mtime',
                'ctime',
                'mimetype',
                'name',
                'identifier',
                'identifier_hash',
                'storage',
                'folder_hash'
            );
        }
        $fileInformation = array();
        foreach ($propertiesToExtract as $property) {
            $fileInformation[$property] = $this->getSpecificFileInformation($filePath, $containerPath, $property);
        }
        return $fileInformation;
    }

    /**
     * Extracts a specific FileInformation from the FileSystems.
     *
     * @param string $fileIdentifier
     * @param string $containerPath
     * @param string $property
     *
     * @return bool|int|string
     * @throws \InvalidArgumentException
     */
    public function getSpecificFileInformation($fileIdentifier, $containerPath, $property)
    {
        $identifier = $this->canonicalizeAndCheckFileIdentifier($containerPath . PathUtility::basename($fileIdentifier));
        $file = $this->filesystem->getMetadata($fileIdentifier);

        switch ($property) {
            case 'size':
                return $file['size'];
            case 'atime':
                return $file['timestamp'];
            case 'mtime':
                return $file['timestamp'];
            case 'ctime':
                return $file['timestamp'];
            case 'name':
                return PathUtility::basename($fileIdentifier);
            case 'mimetype':
                return 'application/octet-stream';
            case 'identifier':
                return $identifier;
            case 'storage':
                return $this->storageUid;
            case 'identifier_hash':
                return $this->hashIdentifier($identifier);
            case 'folder_hash':
                return $this->hashIdentifier($this->getParentFolderIdentifierOfIdentifier($identifier));
            default:
                throw new \InvalidArgumentException(sprintf('The information "%s" is not available.', $property));
        }
    }
}
