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

namespace DMK\MkContentAi\Http\Client;

use DMK\MkContentAi\Domain\Model\Image;
use DMK\MkContentAi\Http\Client\Action\StabilityAiAction;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\Multipart\FormDataPart;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Domain\Model\File;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

class StabilityAiClient extends BaseClient implements ImageApiInterface
{
    /**
     * @var \Symfony\Contracts\HttpClient\HttpClientInterface
     */
    private $client;

    private StabilityAiAction $stabilityAiAction;

    public function __construct()
    {
        $this->client = HttpClient::create();
    }

    public function injectStabilityAction(StabilityAiAction $stabilityAiAction): void
    {
        $this->stabilityAiAction = $stabilityAiAction;
    }

    public function validateApiCall(): \stdClass
    {
        $headers = [
            'Authorization' => $this->getAuthorizationHeader(),
            'Content-Type' => 'application/json',
        ];

        $response = $this->client->request(
            'GET',
            $this->stabilityAiAction->buildFullUrl($this->stabilityAiAction::API_LINK, 'account', []), [
                'headers' => $headers,
            ]
        );

        $response = $this->validateResponse($response->getContent(false));

        return $response;
    }

    private function getAuthorizationHeader(): string
    {
        return 'Bearer '.$this->getApiKey();
    }

    public function getFolderName(): string
    {
        return 'stability_ai';
    }

    /**
     * @return array<Image>
     */
    public function image(string $text): array
    {
        $headers = [
            'Authorization' => $this->getAuthorizationHeader(),
            'Content-Type' => 'application/json',
        ];

        $params = [
            'text_prompts' => [
                [
                    'text' => $text,
                ],
            ],
            'cfg_scale' => 7,
            'clip_guidance_preset' => 'FAST_BLUE',
            'height' => 1024,
            'width' => 1024,
            'samples' => 3,
            'steps' => 30,
        ];

        $response = $this->client->request(
            'POST',
            $this->stabilityAiAction->buildFullUrl($this->stabilityAiAction::API_LINK, 'image', []), [
                'headers' => $headers,
                'body' => json_encode($params),
            ]
        );

        $response = $this->validateResponse($response->getContent(false));

        $images = $this->responseToImages($response);

        return $images;
    }

    /**
     * @return array<Image>
     */
    public function createImageVariation(File $file): array
    {
        $tempLocalCopyPath = $file->getOriginalResource()->getForLocalProcessing(false);

        $formData = new FormDataPart([
            'init_image' => DataPart::fromPath($tempLocalCopyPath),
            'init_image_mode' => 'IMAGE_STRENGTH',
            'image_strength' => '0.35',
            'text_prompts[0][text]' => 'variant of original image',
            'cfg_scale' => '7',
            'clip_guidance_preset' => 'FAST_BLUE',
            'samples' => '3',
            'steps' => '30',
        ]);

        $headers = $formData->getPreparedHeaders()->toArray();
        $headers['Authorization'] = $this->getAuthorizationHeader();

        $response = $this->client->request(
            'POST',
            $this->stabilityAiAction->buildFullUrl($this->stabilityAiAction::API_LINK, 'imageVariation', []), [
                'headers' => $headers,
                'body' => $formData->bodyToIterable(),
            ]
        );

        $response = $this->validateResponse($response->getContent(false));

        $images = $this->responseToImages($response);

        return $images;
    }

    public function upscale(File $file): Image
    {
        $tempLocalCopyPath = $file->getOriginalResource()->getForLocalProcessing(false);
        $resourceFactory = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Resource\ResourceFactory::class);
        $originalFile = $resourceFactory->getFileObject($file->getOriginalResource()->getUid());
        $newWidth = $originalFile->getProperty('width') * 2;
        $formData = new FormDataPart([
            'image' => DataPart::fromPath($tempLocalCopyPath),
            'width' => (string) $newWidth,
        ]);

        $headers = $formData->getPreparedHeaders()->toArray() + [
            'Accept' => 'image/png',
            'Authorization' => $this->getAuthorizationHeader(),
        ];

        $response = $this->client->request(
            'POST',
            $this->stabilityAiAction->buildFullUrl($this->stabilityAiAction::API_LINK, 'upscale', []), [
                'headers' => $headers,
                'body' => $formData->bodyToIterable(),
            ]
        );

        // if response is valid base64 encoded image
        if (200 === $response->getStatusCode()) {
            $image = $this->base64ToImage(base64_encode($response->getContent(false)));

            return $image;
        }
        throw new \Exception('Response code '.$response->getStatusCode());
    }

    /**
     * @return array<Image>
     */
    public function extend(string $sourceImagePath, string $direction = 'right', ?string $promptText = ''): array
    {
        $promptText = $promptText ?? '';
        $formData = $this->prepareFormDataRequest($direction, $sourceImagePath, $promptText);

        $headers = $formData->getPreparedHeaders()->toArray() + [
            'accept' => 'application/json',
            'Authorization' => $this->getAuthorizationHeader(),
        ];

        $response = $this->client->request(
            'POST',
            $this->stabilityAiAction->buildFullUrl($this->stabilityAiAction::API_LINK, 'extend', []),
            [
                'headers' => $headers,
                'body' => $formData->bodyToIterable(),
            ]
        );

        if (200 === $response->getStatusCode()) {
            $response = $this->validateResponse($response->getContent(false));
            $images[] = $this->base64ToImage($response->image);

            return $images;
        }

        throw new \Exception('Response code '.$response->getStatusCode());
    }

    /**
     * @param string|bool $response
     *
     * @throws \Exception
     */
    public function validateResponse($response): \stdClass
    {
        if (!is_string($response)) {
            $translatedMessage = LocalizationUtility::translate('labelResponseNotString', 'mkcontentai') ?? '';

            throw new \Exception($translatedMessage);
        }
        $response = json_decode($response);

        if ($response->message && $response->name) {
            throw new \Exception($response->name.' - '.$response->message);
        }

        return $response;
    }

    public function setDirection(string $direction): string
    {
        switch ($direction) {
            case 'bottom':
                return 'down';
            case 'top':
                return 'up';
        }

        return $direction;
    }

    public function prepareFormDataRequest(string $direction, string $sourceImagePath, string $promptText): FormDataPart
    {
        if ('zoomOut' === $direction) {
            return new FormDataPart([
                'image' => DataPart::fromPath($sourceImagePath),
                'left' => '512',
                'right' => '512',
                'up' => '512',
                'down' => '512',
                'prompt' => $promptText,
            ]);
        }

        return new FormDataPart([
            'image' => DataPart::fromPath($sourceImagePath),
            $this->setDirection($direction) => '512',
            'prompt' => $promptText,
        ]);
    }

    private function base64ToImage(string $base64): Image
    {
        $binaryData = base64_decode($base64);
        $tempFile = GeneralUtility::tempnam('contentai');
        if (is_string($tempFile)) {
            file_put_contents($tempFile, $binaryData);
        }

        return GeneralUtility::makeInstance(Image::class, $tempFile, '', $base64);
    }

    /**
     * @return array<Image>
     */
    private function responseToImages(\stdClass $response): array
    {
        $images = [];
        foreach ($response->artifacts as $image) {
            $images[] = $this->base64ToImage($image->base64);
        }

        return $images;
    }

    public function getAllowedOperations(): array
    {
        return ['upscale', 'variants', 'filelist', 'saveFile', 'promptResult', 'prompt', 'promptResultAjax', 'extend', 'cropAndExtend'];
    }
}
