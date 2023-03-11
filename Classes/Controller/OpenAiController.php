<?php

declare(strict_types=1);

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

namespace DMK\MkContentAi\Controller;

use DMK\MkContentAi\Http\Client\OpenAiClient;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Domain\Model\File;

/**
 * This file is part of the "DMK Content AI" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * (c) 2023
 */

/**
 * ImageController.
 */
class OpenAiController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
{
    public OpenAiClient $client;

    public function initializeAction(): void
    {
        try {
            $this->client = GeneralUtility::makeInstance(OpenAiClient::class);
        } catch (\Exception $e) {
            $this->addFlashMessage($e->getMessage(), '', AbstractMessage::WARNING);
        }
    }

    /**
     * @return \Psr\Http\Message\ResponseInterface
     *
     * @throws \TYPO3\CMS\Core\Resource\Exception\InsufficientFolderAccessPermissionsException
     */
    public function filelistAction()
    {
        $storageRepository = GeneralUtility::makeInstance(StorageRepository::class);
        $storage = $storageRepository->getDefaultStorage();
        if (!is_null($storage)) {
            $filesInFolder = $storage->getFilesInFolder($storage->getDefaultFolder());

            $this->view->assignMultiple(
                [
                    'files' => $filesInFolder,
                ]
            );
        }

        return $this->htmlResponse();
    }

    /**
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function variantsAction(File $file)
    {
        try {
            $response = $this->client->createImageVariation($file);
        } catch (\Exception $e) {
            $this->addFlashMessage($e->getMessage(), '', AbstractMessage::ERROR);
            $this->redirect('filelist');
        }

        $this->view->assignMultiple(
            [
                'response' => $response,
                'originalFile' => $file,
            ]
        );

        return $this->htmlResponse();
    }

    public function listAction(): \Psr\Http\Message\ResponseInterface
    {
        return $this->htmlResponse();
    }

    /**
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function promptAction()
    {
        return $this->htmlResponse();
    }

    /**
     * @return \Psr\Http\Message\ResponseInterface
     *
     * @throws \TYPO3\CMS\Core\Resource\Exception\ExistingTargetFileNameException
     */
    public function promptResultAction(string $text)
    {
        try {
            $response = $this->client->image($text);
        } catch (\Exception $e) {
            $this->addFlashMessage($e->getMessage(), '', AbstractMessage::ERROR);
            $this->redirect('prompt');
        }

        $this->view->assignMultiple(
            [
                'response' => $response,
                'text' => $text,
            ]
        );

        return $this->htmlResponse();
    }

    /**
     * @return void
     *
     * @throws \TYPO3\CMS\Core\Resource\Exception\ExistingTargetFileNameException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     */
    public function saveFileAction(string $imageUrl, string $description = '')
    {
        $storageRepository = GeneralUtility::makeInstance(StorageRepository::class);
        $storage = $storageRepository->getDefaultStorage();
        if (!is_null($storage)) {
            $temporaryFile = GeneralUtility::tempnam('contentai');
            $fileResponse = file_get_contents($imageUrl);
            if (is_string($fileResponse)) {
                GeneralUtility::writeFileToTypo3tempDir(
                    $temporaryFile,
                    $fileResponse
                );

                /** @var \TYPO3\CMS\Core\Resource\File $fileObject */
                $fileObject = $storage->addFile(
                    $temporaryFile,
                    $storage->getDefaultFolder(),
                    time().'.png'
                );

                if ('' == !$description) {
                    $metaData = $fileObject->getMetaData();
                    $metaData->offsetSet('description', $description);
                    $metaData->save();
                }

                $this->addFlashMessage('File has been saved');
            }
        }

        $this->redirect('filelist');
    }
}
