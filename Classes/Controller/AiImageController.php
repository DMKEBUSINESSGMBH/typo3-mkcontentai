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

use DMK\MkContentAi\Http\Client\ClientInterface;
use DMK\MkContentAi\Http\Client\OpenAiClient;
use DMK\MkContentAi\Http\Client\StableDiffusionClient;
use DMK\MkContentAi\Service\FileService;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
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
class AiImageController extends BaseController
{
    public const GENERATOR_ENGINE_KEY = 'image_generator_engine';

    /**
     * @var array<class-string<object>>
     */
    public const GENERATOR_ENGINE = [
        1 => OpenAiClient::class,
        2 => StableDiffusionClient::class,
    ];

    public ClientInterface $client;

    public function initializeAction(): void
    {
        try {
            $imageEngineKey = SettingsController::getImageAiEngine();
            if (!$this::GENERATOR_ENGINE[$imageEngineKey]) {
                $this->addFlashMessage('Image generator engine not defined - please go to settings.', '', AbstractMessage::WARNING);

                return;
            }
            $client = GeneralUtility::makeInstance($this::GENERATOR_ENGINE[$imageEngineKey]);
            if (is_a($client, ClientInterface::class)) {
                $this->client = $client;
                $this->addFlashMessage(get_class($this->client), '', AbstractMessage::INFO);
            }
        } catch (\Exception $e) {
            $this->addFlashMessage($e->getMessage(), '', AbstractMessage::WARNING);
        }
        parent::initializeAction();
    }

    /**
     * @return \Psr\Http\Message\ResponseInterface
     *
     * @throws \TYPO3\CMS\Core\Resource\Exception\InsufficientFolderAccessPermissionsException
     */
    public function filelistAction()
    {
        $fileService = GeneralUtility::makeInstance(FileService::class, $this->client->getFolderName());
        $this->view->assignMultiple(
            [
                'files' => $fileService->getFiles(),
            ]
        );

        if (null === $this->moduleTemplateFactory) {
            throw new \Exception('ModuleTemplateFactory not injected', 1623345720);
        }

        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);
        $moduleTemplate->setContent($this->view->render());

        return $this->htmlResponse($moduleTemplate->renderContent());
    }

    /**
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function variantsAction(File $file)
    {
        try {
            $images = $this->client->createImageVariation($file);
        } catch (\Exception $e) {
            $this->addFlashMessage($e->getMessage(), '', AbstractMessage::ERROR);
            $this->redirect('filelist');
        }

        $this->view->assignMultiple(
            [
                'images' => $images,
                'originalFile' => $file,
            ]
        );

        if (null === $this->moduleTemplateFactory) {
            throw new \Exception('ModuleTemplateFactory not injected', 1623345720);
        }

        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);
        $moduleTemplate->setContent($this->view->render());

        return $this->htmlResponse($moduleTemplate->renderContent());
    }

    /**
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function promptAction()
    {
        if (null === $this->moduleTemplateFactory) {
            throw new \Exception('ModuleTemplateFactory not injected', 1623345720);
        }

        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);
        $moduleTemplate->setContent($this->view->render());

        return $this->htmlResponse($moduleTemplate->renderContent());
    }

    /**
     * @return \Psr\Http\Message\ResponseInterface
     *
     * @throws \TYPO3\CMS\Core\Resource\Exception\ExistingTargetFileNameException
     */
    public function promptResultAction(string $text)
    {
        try {
            $images = $this->client->image($text);
        } catch (\Exception $e) {
            $this->addFlashMessage($e->getMessage(), '', AbstractMessage::ERROR);
            $this->redirect('prompt');
        }

        $this->view->assignMultiple(
            [
                'images' => $images,
                'text' => $text,
            ]
        );

        if (null === $this->moduleTemplateFactory) {
            throw new \Exception('ModuleTemplateFactory not injected', 1623345720);
        }

        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);
        $moduleTemplate->setContent($this->view->render());

        return $this->htmlResponse($moduleTemplate->renderContent());
    }

    public function saveFileAction(string $imageUrl, string $description = ''): ResponseInterface
    {
        $fileService = GeneralUtility::makeInstance(FileService::class, $this->client->getFolderName());
        try {
            $fileService->saveImageFromUrl($imageUrl, $description);
        } catch (\Exception $e) {
            $this->addFlashMessage($e->getMessage(), '', AbstractMessage::ERROR);
        }

        return $this->redirect('filelist');
    }
}
