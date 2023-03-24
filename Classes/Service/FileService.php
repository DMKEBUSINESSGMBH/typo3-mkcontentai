<?php

/*
 * Copyright notice
 *
 * (c) DMK E-BUSINESS GmbH <dev@dmk-ebusiness.de>
 * All rights reserved
 *
 * This file is part of TYPO3 CMS-based extension "container" by b13.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

namespace DMK\MkContentAi\Service;

use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class FileService
{
    private StorageRepository $storageRepository;

    private string $path = 'ai';

    public function __construct()
    {
        $this->storageRepository = GeneralUtility::makeInstance(StorageRepository::class);
    }

    /**
     * @throws \TYPO3\CMS\Core\Resource\Exception\ExistingTargetFileNameException
     * @throws \TYPO3\CMS\Core\Resource\Exception\ExistingTargetFolderException
     * @throws \TYPO3\CMS\Core\Resource\Exception\InsufficientFolderAccessPermissionsException
     * @throws \TYPO3\CMS\Core\Resource\Exception\InsufficientFolderWritePermissionsException
     */
    public function saveImageFromUrl(string $imageUrl, string $description = ''): void
    {
        $storage = $this->getStorage();

        if (!$this->directoryExists()) {
            $storage->createFolder($this->path);
        }

        $temporaryFile = GeneralUtility::tempnam('contentai');
        $fileResponse = file_get_contents($imageUrl);
        if (!is_string($fileResponse)) {
            throw new \Exception($imageUrl . ' can not be fetched.');
        }
        GeneralUtility::writeFileToTypo3tempDir(
            $temporaryFile,
            $fileResponse
        );

        /** @var \TYPO3\CMS\Core\Resource\File $fileObject */
        $fileObject = $storage->addFile(
            $temporaryFile,
            $this->getFolder(),
            time() . '.png'
        );

        if ('' == !$description) {
            $metaData = $fileObject->getMetaData();
            $metaData->offsetSet('description', $description);
            $metaData->save();
        }
    }

    public function directoryExists(): bool
    {
        try {
            $this->getFolder();
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * @return \TYPO3\CMS\Core\Resource\File[]
     *
     * @throws \TYPO3\CMS\Core\Resource\Exception\InsufficientFolderAccessPermissionsException
     */
    public function getFiles(): array
    {
        $storage = $this->getStorage();

        return $storage->getFilesInFolder($this->getFolder());
    }

    /**
     * @return \TYPO3\CMS\Core\Resource\Folder|\TYPO3\CMS\Core\Resource\InaccessibleFolder
     *
     * @throws \TYPO3\CMS\Core\Resource\Exception\InsufficientFolderAccessPermissionsException
     */
    private function getFolder(): Folder
    {
        return $this->getStorage()->getFolder($this->path);
    }

    private function getStorage(): ResourceStorage
    {
        $storage = $this->storageRepository->getDefaultStorage();
        if (is_null($storage)) {
            throw new \Exception('Error getting TYPO3 storage');
        }

        return $storage;
    }
}
