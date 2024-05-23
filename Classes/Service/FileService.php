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

use DMK\MkContentAi\DTO\FileAltTextDTO;
use TYPO3\CMS\Core\Imaging\GraphicalFunctions;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\Index\MetaDataRepository;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Domain\Model\File;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

class FileService
{
    public GraphicalFunctions $graphicalFunctions;
    public MetaDataRepository $metaDataRepository;
    private StorageRepository $storageRepository;
    private ResourceFactory $resourceFactory;

    private string $path = 'mkcontentai';

    public function __construct(?string $folder = null)
    {
        $this->path = 'mkcontentai/'.$folder;
        $this->graphicalFunctions = GeneralUtility::makeInstance(GraphicalFunctions::class);
        $this->metaDataRepository = GeneralUtility::makeInstance(MetaDataRepository::class);
        $this->storageRepository = GeneralUtility::makeInstance(StorageRepository::class);
        $this->resourceFactory = GeneralUtility::makeInstance(ResourceFactory::class);
    }

    public function saveFileFromUrl(string $imageUrl, string $description = '', string $filename = '', string $extension = '.png'): void
    {
        $storage = $this->getStorage();

        if (!$storage->hasFolder($this->path)) {
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

        $filename = ($filename ?: time()).$extension;

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

    /**
     * @return \TYPO3\CMS\Core\Resource\File[]
     */
    public function getFiles(): array
    {
        $storage = $this->getStorage();

        if (!$storage->hasFolder($this->path)) {
            $storage->createFolder($this->path);
        }

        return $storage->getFilesInFolder($storage->getFolder($this->path));
    }

    /**
     * @return \TYPO3\CMS\Core\Resource\File[]
     */
    public function getFilesFromExistingFolder(?string $folder): array
    {
        $storage = $this->getStorage($folder);

        if (null === $folder) {
            $folder = $storage->createFolder($this->path);

            return $storage->getFilesInFolder($folder);
        }

        return $storage->getFilesInFolder($this->resourceFactory->getFolderObjectFromCombinedIdentifier($folder));
    }

    /**
     * @return array<int|string, FileAltTextDTO>
     */
    public function getFilesWithoutAltText(?string $folder): array
    {
        $altTextFromImages = $this->getAltTextFromMetadataOfFiles($folder);

        return \array_filter(
            $altTextFromImages,
            fn (FileAltTextDTO $record) => empty($record->getAltText())
        );
    }

    /**
     * @return array<int|string, FileAltTextDTO>
     */
    public function getAltTextFromMetadataOfFiles(?string $folder): array
    {
        $listOfFiles = $this->getFilesFromExistingFolder($folder);
        $filesAltText = [];
        foreach ($listOfFiles as $file) {
            $filesAltText[$file->getProperty('uid')] = FileAltTextDTO::fromArray(
                $file->getProperty('uid'),
                $this->metaDataRepository->findByFileUid($file->getProperty('uid'))['alternative']
            );
        }

        return $filesAltText;
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
            $translatedMessage = LocalizationUtility::translate('labelErrorInvalidImageType', 'mkcontentai') ?? '';

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

    public function findByCombinedIdentifier(string $identifier): ?ResourceStorage
    {
        $parts = GeneralUtility::trimExplode(':', $identifier);

        return 2 === count($parts) ? $this->storageRepository->findByUid((int) $parts[0]) : null;
    }

    private function getStorage(?string $storageIdentifier = null): ResourceStorage
    {
        $storage = (null === $storageIdentifier) ?
            $this->resourceFactory->getDefaultStorage() :
            $this->findByCombinedIdentifier($storageIdentifier);

        if (null === $storage) {
            $translatedMessage = LocalizationUtility::translate('labelErrorStorage', 'mkcontentai') ?? '';

            throw new \Exception($translatedMessage);
        }

        return $storage;
    }

    /**
     * @return Folder|\TYPO3\CMS\Core\Resource\InaccessibleFolder
     */
    private function getFolder(?string $storageIdentifier = null): Folder
    {
        $storage = $this->getStorage($storageIdentifier);

        return $storage->getFolder($this->path);
    }
}
