<?php

/*
 * Copyright notice
 *
 * (c) DMK E-BUSINESS GmbH <dev@dmk-ebusiness.de>
 * All rights reserved
 *
 * This file is part of TYPO3 CMS-based extension "mkcontentai" by DMK E-BUSINESS GmbH.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

namespace DMK\MkContentAi\Service;

use TYPO3\CMS\Core\Imaging\GraphicalFunctions;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Domain\Model\File;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

class FileService
{
    private StorageRepository $storageRepository;
    private ResourceFactory $resourceFactory;
    public GraphicalFunctions $graphicalFunctions;

    private string $path = 'mkcontentai';

    public function __construct(string $folder = null)
    {
        $this->path = 'mkcontentai/'.$folder;
        $this->storageRepository = GeneralUtility::makeInstance(StorageRepository::class);
        $this->graphicalFunctions = GeneralUtility::makeInstance(GraphicalFunctions::class);
        $this->resourceFactory = GeneralUtility::makeInstance(ResourceFactory::class);
    }

    public function saveImageFromUrl(string $imageUrl, string $description = '', string $filename = ''): void
    {
        $storage = $this->getStorage();

        if (!$this->directoryExists()) {
            $storage->createFolder($this->path);
        }

        $temporaryFile = GeneralUtility::tempnam('contentai');
        $fileResponse = GeneralUtility::getUrl($imageUrl);
        if (!is_string($fileResponse)) {
            $translatedMessage = LocalizationUtility::translate('labelErrorCantBeFetched', 'mkcontentai') ?? '';

            throw new \Exception($imageUrl.' '.$translatedMessage);
        }
        GeneralUtility::writeFileToTypo3tempDir(
            $temporaryFile,
            $fileResponse
        );

        $filename = ($filename ?: time()).'.png';

        /** @var \TYPO3\CMS\Core\Resource\File $fileObject */
        $fileObject = $storage->addFile(
            $temporaryFile,
            $this->getFolder(),
            $filename
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
     */
    public function getFiles(): array
    {
        $storage = $this->getStorage();

        if (!$this->directoryExists()) {
            $storage->createFolder($this->path);
        }

        return $storage->getFilesInFolder($this->getFolder());
    }

    /**
     * @return Folder|\TYPO3\CMS\Core\Resource\InaccessibleFolder
     */
    private function getFolder(): Folder
    {
        return $this->getStorage()->getFolder($this->path);
    }

    private function getStorage(): ResourceStorage
    {
        $storage = $this->storageRepository->getDefaultStorage();
        if (is_null($storage)) {
            $translatedMessage = LocalizationUtility::translate('labelErrorStorage', 'mkcontentai') ?? '';

            throw new \Exception($translatedMessage);
        }

        return $storage;
    }

    public function saveTempBase64Image(string $base64): string
    {
        if (preg_match('/^data:image\/(\w+);base64,/', $base64, $type)) {
            $type = strtolower($type[1]); // The extracted type
            if (!in_array($type, ['jpg', 'jpeg', 'gif', 'png'])) {
                $translatedMessage = LocalizationUtility::translate('labelErrorInvalidImageType', 'mkcontentai') ?? '';

                throw new \Exception($translatedMessage);
            }
        }
        $base64Image = explode(';base64,', $base64)[1];
        $binaryData = base64_decode($base64Image);
        $tempFile = GeneralUtility::tempnam('contentai');
        if (false === $tempFile) {
            $translatedMessage = LocalizationUtility::translate('labelErrorTempFile', 'mkcontentai') ?? '';

            throw new \Exception($translatedMessage);
        }
        if (is_string($tempFile) && is_string($type)) {
            $tempFile = $tempFile.'.'.$type;
            file_put_contents($tempFile, $binaryData);
        }

        return $tempFile;
    }

    public function getFileById(string $fileId): ?File
    {
        try {
            $fileOriginalResource = $this->resourceFactory->getFileObject((int) $fileId);
            $file = new File();
            $file->setOriginalResource($fileOriginalResource);
        } catch (\Exception $e) {
            return null;
        }

        return $file;
    }
}
