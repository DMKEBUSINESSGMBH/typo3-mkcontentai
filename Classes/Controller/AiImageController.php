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

        $infoMessage = 'Image AI Engine initialized';
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
        $actionMethodName = $this->request->getControllerActionName();
        if (!in_array($actionMethodName, $this->client->getAllowedOperations())) {
            $this->controllerContext = $this->buildControllerContext();
            $this->addFlashMessage($actionMethodName.' is not allowed for current API '.get_class($this->client), '', AbstractMessage::ERROR);
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

            return [
                'error' => 'Something wrong',
            ];
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * @throws \TYPO3\CMS\Core\Resource\Exception\InsufficientFolderAccessPermissionsException
     */
    public function filelistAction(): void
    {
        $clientResponse = $this->initializeClient();

        if (!isset($clientResponse['client'])) {
            $this->addFlashMessage('Please set AI client in settings first', '', AbstractMessage::WARNING);
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

    /**
     * @return \Psr\Http\Message\ResponseInterface
     *
     * @throws \TYPO3\CMS\Core\Resource\Exception\ExistingTargetFileNameException
     */
    public function promptResultAjaxAction(ServerRequestInterface $request)
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
            throw new \Exception('Client is not defined', 1623345720);
        }
        $client = $clientResponse['client'];

        if (empty($request->getParsedBody()['promptText'])) {
            return new JsonResponse(
                [
                    'error' => 'You must provide a prompt text.',
                ],
                500);
        }
        $text = $request->getParsedBody()['promptText'];

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

    /**
     * @throws \TYPO3\CMS\Core\Resource\Exception\ExistingTargetFileNameException
     */
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

        $this->addFlashMessage('Upscaled image saved', '', AbstractMessage::INFO);

        $this->redirect('filelist');
    }

    public function extendAction(string $direction, File $file = null, string $base64 = ''): void
    {
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
                throw new \Exception('No file provided', 1623345720);
            }
            $images = $this->client->extend($filePath, $direction);
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

    public function cropAndExtendAction(File $file): void
    {
        $this->view->assignMultiple(
            [
                'file' => $file,
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
