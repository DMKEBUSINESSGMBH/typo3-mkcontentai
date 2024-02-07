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
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\Multipart\FormDataPart;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Domain\Model\File;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

class StabilityAiClient extends BaseClient implements ImageApiInterface
{
    private const API_LINK = 'https://api.stability.ai/';

    /**
     * @var \Symfony\Contracts\HttpClient\HttpClientInterface
     */
    private $client;

    public function __construct()
    {
        $this->client = HttpClient::create();
    }

    public function validateApiCall(): \stdClass
    {
        $headers = [
            'Authorization' => $this->getAuthorizationHeader(),
            'Content-Type' => 'application/json',
        ];

        $response = $this->client->request(
            'GET',
            $this->getEndpointLink('v1/user/account'),
            [
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

    private function getEndpointLink(string $path): string
    {
        return self::API_LINK.$path;
    }

    public function getApiLink(): string
    {
        return self::API_LINK;
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
            $this->getEndpointLink('v1/generation/stable-diffusion-xl-1024-v1-0/text-to-image'),
            [
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
            $this->getEndpointLink('v1/generation/stable-diffusion-xl-1024-v1-0/image-to-image'),
            [
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
            $this->getEndpointLink('v1/generation/esrgan-v1-x2plus/image-to-image/upscale'),
            [
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
    public function extend(string $sourceImage, string $text = 'outpaint'): array
    {
        $translatedMessage = LocalizationUtility::translate('labelErrorNotImplemented', 'mkcontentai') ?? '';

        throw new \Exception($translatedMessage);
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
        return ['upscale', 'variants', 'filelist', 'saveFile', 'promptResult', 'prompt', 'promptResultAjax'];
    }
}
