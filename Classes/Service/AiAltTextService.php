<?php

declare(strict_types=1);

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

use DMK\MkContentAi\Backend\Event\AiAltTextGeneratedEvent;
use DMK\MkContentAi\DTO\FileAltTextDTO;
use DMK\MkContentAi\Http\Client\AltTextClient;
use DMK\MkContentAi\Http\Client\ClientInterface;
use DMK\MkContentAi\Http\Client\OpenAiAltTextClient;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Domain\Model\File;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;

class AiAltTextService
{
    private const PROVIDER_REGISTRY_NAMESPACE = AltTextClient::class;
    private const PROVIDER_REGISTRY_KEY = 'altTextProvider';
    public const PROVIDER_ALTTEXT_AI = 'alttext.ai';
    public const PROVIDER_OPENAI = 'openai';

    public AltTextClient $altTextClient;
    public OpenAiAltTextClient $openAiAltTextClient;
    public FileService $fileService;
    protected EventDispatcher $eventDispatcher;
    private int $skippedAltTextForFiles = 0;
    private int $failedProcessedImages = 0;
    private int $generatedAltTexts = 0;
    private int $fileIsNotImage = 0;
    private int $hasAltText = 0;

    public function __construct(
        AltTextClient $altTextClient,
        OpenAiAltTextClient $openAiAltTextClient,
        FileService $fileService,
        EventDispatcher $eventDispatcher
    ) {
        $this->altTextClient = $altTextClient;
        $this->openAiAltTextClient = $openAiAltTextClient;
        $this->fileService = $fileService;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     */
    private function getActiveAltTextClient(): ClientInterface
    {
        $registry = GeneralUtility::makeInstance(Registry::class);
        $provider = $registry->get(self::PROVIDER_REGISTRY_NAMESPACE, self::PROVIDER_REGISTRY_KEY, self::PROVIDER_ALTTEXT_AI);

        if (self::PROVIDER_OPENAI === $provider) {
            return $this->openAiAltTextClient;
        }

        return $this->altTextClient;
    }

    /**
     * Saves the active alt text provider to the registry.
     */
    public static function setAltTextProvider(string $provider): void
    {
        $registry = GeneralUtility::makeInstance(Registry::class);
        $registry->set(self::PROVIDER_REGISTRY_NAMESPACE, self::PROVIDER_REGISTRY_KEY, $provider);
    }

    /**
     * Returns the currently stored alt text provider identifier.
     */
    public static function getAltTextProvider(): string
    {
        $registry = GeneralUtility::makeInstance(Registry::class);
        return (string) ($registry->get(self::PROVIDER_REGISTRY_NAMESPACE, self::PROVIDER_REGISTRY_KEY, self::PROVIDER_ALTTEXT_AI) ?? self::PROVIDER_ALTTEXT_AI);
    }

    /**
     * @throws \Exception
     */
    public function getAltText(File $file, ?string $languageIsoCode = null): string
    {
        $client = $this->getActiveAltTextClient();

        try {
            $altText = $client->getByAssetId($file->getOriginalResource()->getUid(), $languageIsoCode);
        } catch (\Exception $e) {
            if (404 != $e->getCode()) {
                throw $e;
            }
            $altText = $client->getAltTextForFile($file, $languageIsoCode);

            return $altText;
        }

        return $altText;
    }

    /**
     * @return \TYPO3\CMS\Core\Resource\File[]
     */
    public function getListOfFiles(string $folderName): array
    {
        return $this->fileService->getFilesFromExistingFolder($folderName);
    }

    /**
     * @return array<int|string, FileAltTextDTO>
     */
    public function getEmptyAltTextFiles(string $folderName): array
    {
        return $this->fileService->getFilesWithoutAltText($folderName);
    }

    public function getFileById(string $fileId): ?File
    {
        return $this->fileService->getFileById($fileId);
    }

    /**
     * @return array<int|string, FileAltTextDTO>
     */
    public function getMultipleAltTextsForImages(string $folderName): array
    {
        $finalFilesWithAltText = [];
        $files = $this->getListOfFiles($folderName);
        $emptyAltTextFiles = $this->getEmptyAltTextFiles($folderName);

        foreach ($files as $file) {
            $fileUid = $file->getUid();
            $fileById = $this->getFileById((string) $fileUid);
            if (null == $fileById) {
                continue;
            }
            if (!empty($fileById->getOriginalResource()->getProperty('alternative'))) {
                $this->incrementHasAltText();
                continue;
            }
            if (!$this->isSupportedImage($file->getExtension())) {
                $this->incrementFileIsNotImage();
                continue;
            }
            if (!isset($emptyAltTextFiles[$fileUid])) {
                $this->incrementSkippedAltTextFiles();
                continue;
            }
            try {
                $emptyAltTextFiles[$fileUid]->setAltText($this->getAltText($fileById));
                $emptyAltTextFiles[$fileUid]->setFile($file);
                $finalFilesWithAltText[$fileUid] = $emptyAltTextFiles[$fileUid];
                $this->incrementGeneratedAltText();
            } catch (\Exception $e) {
                $this->incrementFailedProcessedImages();
                continue;
            }
        }

        return $finalFilesWithAltText;
    }

    /**
     * @param FileAltTextDTO[] $altTexts
     */
    public function saveAltTextsMetaData(array $altTexts): void
    {
        foreach ($altTexts as $fileAltTextDTO) {
            if (null == $fileAltTextDTO->getFile()) {
                continue;
            }
            $altText = $fileAltTextDTO->getAltText() ?? '';
            $metadata = $fileAltTextDTO->getFile()->getMetaData();
            $metadata->offsetSet('alternative', $altText);
            $metadata->save();
            $this->processGeneratedAltTextLog('sys_file_metadata', $metadata['uid'], $altText);
        }
    }

    public function getFailedProcessedImages(): int
    {
        return $this->failedProcessedImages;
    }

    public function getGeneratedAltTexts(): int
    {
        return $this->generatedAltTexts;
    }

    public function getFileIsNotImage(): int
    {
        return $this->fileIsNotImage;
    }

    public function getHasAltText(): int
    {
        return $this->hasAltText;
    }

    public function incrementSkippedAltTextFiles(): void
    {
        ++$this->skippedAltTextForFiles;
    }

    public function incrementGeneratedAltText(): void
    {
        ++$this->generatedAltTexts;
    }

    public function incrementFailedProcessedImages(): void
    {
        ++$this->failedProcessedImages;
    }

    public function incrementFileIsNotImage(): void
    {
        ++$this->fileIsNotImage;
    }

    public function incrementHasAltText(): void
    {
        ++$this->hasAltText;
    }

    public function processGeneratedAltTextLog(string $tableName, int $resourceUid, string $altText): void
    {
        $event = new AiAltTextGeneratedEvent($tableName, $resourceUid, $altText);
        $this->eventDispatcher->dispatch($event);
    }

    protected function isSupportedImage(string $fileExtension): bool
    {
        $supportedFormatsImages = ['jpg', 'png', 'gif', 'webp', 'bmp'];

        return in_array($fileExtension, $supportedFormatsImages);
    }
}
