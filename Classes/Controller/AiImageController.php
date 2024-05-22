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

use DMK\MkContentAi\Domain\Model\Image;
use DMK\MkContentAi\Http\Client\ImageApiInterface;
use DMK\MkContentAi\Http\Client\OpenAiClient;
use DMK\MkContentAi\Http\Client\StabilityAiClient;
use DMK\MkContentAi\Http\Client\StableDiffusionClient;
use DMK\MkContentAi\Service\FileService;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Domain\Model\File;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

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
        3 => StabilityAiClient::class,
    ];

    public ImageApiInterface $client;

    public function initializeAction(): void
    {
        $client = $this->initializeClient();
        $typo3Version = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Information\Typo3Version::class);
        if (isset($client['error'])) {
            if ($typo3Version->getMajorVersion() > 10) {
                $this->addFlashMessage(
                    $client['error'],
                    '',
                    AbstractMessage::ERROR
                );
            }

            return;
        }
        if (isset($client['client'])) {
            $this->client = $client['client'];
        }

        $infoMessage = LocalizationUtility::translate('labelEngineInitialized', 'mkcontentai') ?? '';
        if (isset($client['clientClass'])) {
            $infoMessage .= ' '.$client['clientClass'];
        }
        if ($typo3Version->getMajorVersion() > 10) {
            $this->addFlashMessage(
                $infoMessage,
                '',
                AbstractMessage::INFO
            );
        }

        $arguments['actionName'] = $this->request->getControllerActionName();
        if (!in_array($arguments['actionName'], $this->client->getAllowedOperations())) {
            $this->controllerContext = $this->buildControllerContext();
            $translatedMessage = LocalizationUtility::translate('labelNotAllowed', 'mkcontentai', $arguments) ?? '';
            $this->addFlashMessage($translatedMessage.' '.get_class($this->client), '', AbstractMessage::ERROR);

            $this->redirect('filelist');
        }
        parent::initializeAction();
    }

    /**
     * @return array{client?:ImageApiInterface, clientClass?:string, error?:string}
     */
    private function initializeClient(): array
    {
        try {
            $imageEngineKey = SettingsController::getImageAiEngine();
            $client = GeneralUtility::makeInstance($this::GENERATOR_ENGINE[$imageEngineKey]);
            if (is_a($client, ImageApiInterface::class)) {
                return [
                    'client' => $client,
                    'clientClass' => get_class($client),
                ];
            }
            $errorTranslated = LocalizationUtility::translate('labelError', 'mkcontentai') ?? '';

            return [
                'error' => $errorTranslated,
            ];
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage(),
            ];
        }
    }

    public function filelistAction(): void
    {
        $clientResponse = $this->initializeClient();

        if (!isset($clientResponse['client'])) {
            $translatedMessage = LocalizationUtility::translate('labelSetClient', 'mkcontentai') ?? '';
            $this->addFlashMessage($translatedMessage, '', AbstractMessage::WARNING);
            $this->view->assignMultiple(
                [
                    'files' => [],
                    'client' => null,
                ]
            );

            return;
        }

        $client = $clientResponse['client'];
        $fileService = GeneralUtility::makeInstance(FileService::class, $client->getFolderName());
        $this->view->assignMultiple(
            [
                'files' => $fileService->getFiles(),
                'client' => $client,
            ]
        );
    }

    public function promptResultAjaxAction(ServerRequestInterface $request): JsonResponse
    {
        $clientResponse = $this->initializeClient();

        if (isset($clientResponse['error'])) {
            return new JsonResponse(
                [
                    'error' => $clientResponse['error'],
                ],
                500);
        }
        if (!isset($clientResponse['client'])) {
            $translatedMessage = LocalizationUtility::translate('labelErrorClientIsNotDefined', 'mkcontentai') ?? '';

            throw new \Exception($translatedMessage, 1623345720);
        }
        $client = $clientResponse['client'];

        /** @var array<mixed> $parsedBody */
        $parsedBody = $request->getParsedBody();

        $text = array_key_exists('promptText', $parsedBody) ? $parsedBody['promptText'] : null;

        if (empty($text)) {
            $translatedMessage = LocalizationUtility::translate('labelErrorPromptText', 'mkcontentai') ?? '';

            return new JsonResponse(
                [
                    'error' => $translatedMessage,
                ],
                500);
        }

        try {
            $images = $client->image($text);
            /** @var Image[] $images */
            foreach ($images as $key => $image) {
                $images[$key] = $image->toArray();
            }
            $data = [
                'name' => get_class($client),
                'images' => $images,
            ];
        } catch (\Exception $e) {
            return new JsonResponse(
                [
                    'error' => $e->getMessage(),
                ],
                500);
        }

        return new JsonResponse($data, 200);
    }

    public function variantsAction(File $file): void
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
    }

    public function promptAction(): void
    {
    }

    public function promptResultAction(string $text): void
    {
        try {
            $clientResponse = $this->initializeClient();
            $client = $clientResponse['client'] ?? null;
            if (null === $client) {
                throw new \Exception(isset($clientResponse['error']) ? $clientResponse['error'] : 'Something went wrong');
            }
            $images = $client->image($text);
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
    }

    public function upscaleAction(File $file): void
    {
        try {
            $upscaledImage = $this->client->upscale($file);
        } catch (\Exception $e) {
            $this->addFlashMessage($e->getMessage(), '', AbstractMessage::ERROR);

            $this->redirect('filelist');
        }

        $fileService = GeneralUtility::makeInstance(FileService::class, $this->client->getFolderName());
        $fileService->saveImageFromUrl($upscaledImage->getUrl(), 'upscaled image', $file->getOriginalResource()->getNameWithoutExtension().'_upscaled');
        $translatedMessage = LocalizationUtility::translate('mlang_label_upscaled_image_saved', 'mkcontentai') ?? '';
        $this->addFlashMessage($translatedMessage, '', AbstractMessage::INFO);

        $this->redirect('filelist');
    }

    public function extendAction(string $direction, ?File $file = null, string $base64 = '', ?string $promptText = ''): void
    {
        if (!isset($promptText) || '' === $promptText) {
            $promptText = 'extend image content';
        }

        try {
            $filePath = '';
            if ($base64) {
                $fileService = GeneralUtility::makeInstance(FileService::class, $this->client->getFolderName());
                $filePath = $fileService->saveTempBase64Image($base64);
            }
            if ($file) {
                $filePath = $file->getOriginalResource()->getForLocalProcessing(false);
            }
            if ('' == $filePath) {
                $translatedMessage = LocalizationUtility::translate('labelErrorNoFileProvided', 'mkcontentai') ?? '';

                throw new \Exception($translatedMessage, 1623345720);
            }
            $images = $this->client->extend($filePath, $direction, $promptText);
        } catch (\Exception $e) {
            $this->addFlashMessage($e->getMessage(), '', AbstractMessage::ERROR);
            $this->redirect('filelist');
        }

        $this->view->assignMultiple(
            [
                'images' => $images,
                'originalFile' => $file,
                'promptText' => $promptText,
            ]
        );
    }

    public function cropAndExtendAction(File $file, ?string $promptText = ''): void
    {
        $this->view->assignMultiple(
            [
                'file' => $file,
                'promptText' => $promptText,
                'clientApi' => substr(get_class($this->client), 28),
            ]
        );
    }

    public function saveFileAction(string $imageUrl, string $description = ''): void
    {
        $fileService = GeneralUtility::makeInstance(FileService::class, $this->client->getFolderName());
        try {
            $fileService->saveImageFromUrl($imageUrl, $description);
        } catch (\Exception $e) {
            $this->addFlashMessage($e->getMessage(), '', AbstractMessage::ERROR);
        }

        $this->redirect('filelist');
    }
}
