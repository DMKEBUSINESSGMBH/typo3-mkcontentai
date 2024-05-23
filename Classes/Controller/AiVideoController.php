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

namespace DMK\MkContentAi\Controller;

use DMK\MkContentAi\Service\FileService;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Domain\Model\File;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * This file is part of the "MK Content AI" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * (c) 2023
 */

/**
 * AiVideoController.
 */
class AiVideoController extends BaseController
{
    private FileService $fileService;

    public function __construct(FileService $fileService)
    {
        $this->fileService = $fileService;
    }

    public function initializeAction(): void
    {
        $this->initializeAndAuthorizeAction();
    }

    /**
     * @return never|void|null
     *
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     */
    public function imageToVideoAction(File $file, string $base64)
    {
        $this->initializeAction();

        try {
            $filePath = $this->fileService->saveTempBase64Image($base64);
            $generatedVideo = $this->client->imageToVideo($filePath);
        } catch (\Exception $e) {
            $this->addFlashMessage($e->getMessage(), '', AbstractMessage::ERROR);

            return $this->redirect('filelist', 'AiImage');
        }
        $translatedMessage = LocalizationUtility::translate('mlang_label_image_to_video_generated', 'mkcontentai') ?? '';

        $this->view->assignMultiple(
            [
                'croppedImage' => $base64,
                'sourceFile' => $file,
                'generatedVideo' => $generatedVideo,
                'clientApi' => substr(get_class($this->client), 28),
            ]
        );
        $this->addFlashMessage($translatedMessage, '', AbstractMessage::INFO);
    }

    /**
     * @return void
     */
    public function prepareImageToVideoAction(File $file)
    {
        $this->view->assignMultiple(
            [
                'options' => $this->client->getAvailableResolutions($this->request->getControllerActionName()),
                'file' => $file,
                'clientApi' => substr(get_class($this->client), 28),
                'actionName' => 'imageToVideo',
                'operationName' => $this->request->getControllerActionName(),
                'controllerName' => $this->request->getControllerName(),
                'withExtend' => false,
            ]
        );
    }

    /**
     * @return never|null
     *
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     */
    public function saveFileAction(File $sourceFile, string $videoUrl)
    {
        $fileService = GeneralUtility::makeInstance(FileService::class, $this->client->getFolderName());
        try {
            $fileService->saveFileFromUrl($videoUrl, $sourceFile->getOriginalResource()->getNameWithoutExtension().' - generated video', $sourceFile->getOriginalResource()->getNameWithoutExtension().'_generated_video', '.mp4');
        } catch (\Exception $e) {
            $this->addFlashMessage($e->getMessage(), '', AbstractMessage::ERROR);
        }

        return $this->redirect('filelist', 'AiImage');
    }
}
