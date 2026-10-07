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

use DMK\MkContentAi\Http\Client\ImageApiInterface;
use DMK\MkContentAi\Http\Client\OpenAiClient;
use DMK\MkContentAi\Http\Client\StabilityAiClient;
use DMK\MkContentAi\Http\Client\StableDiffusionClient;
use DMK\MkContentAi\Service\FileService;
use Psr\Http\Message\ResponseInterface;
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
 * ImageController.
 */
class AiImageController extends BaseController
{
    public const GENERATOR_ENGINE_KEY = 'image_generator_engine';

    /**
     * @var array<class-string<object>>
     */
    public const GENERATOR_ENGINE = [
        1 => OpenAiClient::class,
        2 => StableDiffusionClient::class,
        3 => StabilityAiClient::class,
    ];

    public ImageApiInterface $client;

    public function initializeAction(): void
    {
        $this->initializeAndAuthorizeAction();
    }

    /**
     * @return ResponseInterface
     *
     * @throws \TYPO3\CMS\Core\Resource\Exception\InsufficientFolderAccessPermissionsException
     */
    public function filelistAction()
    {
        $fileService = GeneralUtility::makeInstance(FileService::class, $this->client->getFolderName());
        $this->view->assignMultiple(
            [
                'files' => $fileService->getFiles(),
                'client' => $this->client,
            ]
        );

        return $this->handleResponse();
    }

    /**
     * @return ResponseInterface
     */
    public function variantsAction(File $file)
    {
        try {
            $images = $this->client->createImageVariation($file);
        } catch (\Exception $e) {
            $this->addFlashMessage($e->getMessage(), '', AbstractMessage::ERROR);

            return $this->redirect('filelist');
        }

        $this->view->assignMultiple(
            [
                'images' => $images,
                'originalFile' => $file,
            ]
        );

        return $this->handleResponse();
    }

    /**
     * @return ResponseInterface
     */
    public function promptAction()
    {
        return $this->handleResponse();
    }

    /**
     * @return ResponseInterface
     *
     * @throws \TYPO3\CMS\Core\Resource\Exception\ExistingTargetFileNameException
     */
    public function promptResultAction(string $text)
    {
        try {
            $images = $this->client->image($text);
        } catch (\Exception $e) {
            $this->addFlashMessage($e->getMessage(), '', AbstractMessage::ERROR);

            return $this->redirect('prompt');
        }

        $this->view->assignMultiple(
            [
                'images' => $images,
                'text' => $text,
                'clientApi' => $this->client->getClientName(),
                'generatedAt' => date('Y-m-d H:i:s'),
            ]
        );

        return $this->handleResponse();
    }

    /**
     * @return ResponseInterface
     */
    public function upscaleAction(File $file)
    {
        try {
            $upscaledImage = $this->client->upscale($file);
        } catch (\Exception $e) {
            $this->addFlashMessage($e->getMessage(), '', AbstractMessage::ERROR);

            return $this->redirect('filelist');
        }

        $fileService = GeneralUtility::makeInstance(FileService::class, $this->client->getFolderName());
        $fileService->saveFileFromUrl($upscaledImage->getUrl(), 'upscaled image', $file->getOriginalResource()->getNameWithoutExtension().'_upscaled');
        $translatedMessage = LocalizationUtility::translate('mlang_label_upscaled_image_saved', 'mkcontentai') ?? '';

        $this->addFlashMessage($translatedMessage, '', AbstractMessage::INFO);

        return $this->redirect('filelist');
    }

    /**
     * @return ResponseInterface
     */
    public function extendAction(string $direction, ?File $file = null, string $base64 = '', ?string $promptText = '')
    {
        if (!isset($promptText) || '' === $promptText) {
            $promptText = 'extend image content';
        }

        try {
            $fileService = GeneralUtility::makeInstance(FileService::class, $this->client->getFolderName());
            $filePath = $fileService->getFilePath($base64, $file);

            if ('' == $filePath) {
                $translatedMessage = LocalizationUtility::translate('labelErrorNoFileProvided', 'mkcontentai') ?? '';

                throw new \Exception($translatedMessage, 1623345720);
            }
            $images = $this->client->extend($filePath, $direction, $promptText);
        } catch (\Exception $e) {
            if (403 === $e->getCode() || strpos($e->getMessage(), '401')) {
                $this->addFlashMessage(LocalizationUtility::translate('labelErrorInvalidApiKey', 'mkcontentai', [$this->client->getClientName()]) ?? '', '', AbstractMessage::ERROR);
            }

            $this->addFlashMessage($e->getMessage(), '', AbstractMessage::ERROR);

            return $this->redirect('filelist');
        }

        $this->view->assignMultiple(
            [
                'images' => $images,
                'originalFile' => $file,
                'promptText' => $promptText,
            ]
        );

        return $this->handleResponse();
    }

    public function cropAndExtendAction(File $file, ?string $promptText = ''): ResponseInterface
    {
        if (!isset($promptText) || '' === $promptText) {
            $promptText = 'extending content image';
        }

        $this->view->assignMultiple(
            [
                'options' => $this->client->getAvailableResolutions($this->request->getControllerActionName()),
                'file' => $file,
                'actionName' => 'extend',
                'operationName' => 'extend',
                'controllerName' => $this->request->getControllerName(),
                'withExtend' => true,
                'promptText' => $promptText,
                'clientApi' => $this->client->getClientName(),
            ]
        );

        return $this->handleResponse();
    }

    public function saveFileAction(string $imageUrl, string $description = ''): ResponseInterface
    {
        $fileService = GeneralUtility::makeInstance(FileService::class, $this->client->getFolderName());
        try {
            $fileService->saveFileFromUrl($imageUrl, $description);
            $translatedMessage = LocalizationUtility::translate('labelImageSaved', 'mkcontentai', [$description, $this->client->getClientName(), date('Y-m-d H:i:s')]) ?? '';
            $this->addFlashMessage($translatedMessage, '', AbstractMessage::OK);
        } catch (\Exception $e) {
            $this->addFlashMessage($e->getMessage(), '', AbstractMessage::ERROR);
        }

        return $this->redirect('filelist');
    }

    protected function handleResponse(): ResponseInterface
    {
        if (null === $this->moduleTemplateFactory) {
            $translatedMessage = LocalizationUtility::translate('labelErrorModuleTemplateFactory', 'mkcontentai') ?? '';

            throw new \Exception($translatedMessage, 1623345720);
        }

        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);
        $moduleTemplate->setContent($this->view->render());

        return $this->htmlResponse($moduleTemplate->renderContent());
    }
}
