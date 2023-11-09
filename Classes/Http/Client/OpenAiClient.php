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
use DMK\MkContentAi\Service\ExtendService;
use Orhanerday\OpenAi\OpenAi;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Domain\Model\File;

class OpenAiClient extends BaseClient implements ClientInterface
{
    public function __construct()
    {
        $this->getApiKey();
    }

    public function image(string $text): array
    {
        $openAi = new OpenAi($this->getApiKey());

        $array = [
            'prompt' => $text,
            'n' => 2,
            'size' => '256x256',
        ];

        $response = $this->validateResponse($openAi->image($array));

        $images = $this->responseToImages($response);

        return $images;
    }

    public function createImageVariation(File $file): array
    {
        $openAi = new OpenAi($this->getApiKey());

        $array = [
            'image' => $file->getOriginalResource()->getContents(),
            'n' => 3,
            'size' => '1024x1024',
        ];

        $stream = curl_file_create(Environment::getPublicPath().'/'.$file->getOriginalResource()->getPublicUrl(), 'r');

        $array['image'] = $stream;

        $response = $this->validateResponse($openAi->createImageVariation($array));

        $images = $this->responseToImages($response);

        return $images;
    }

    public function upscale(File $file): Image
    {
        throw new \Exception('Upscale not implemented for OpenAi yet');
    }

    /**
     * @return array<Image>
     */
    public function extend(string $sourceImagePath, string $direction = 'right'): array
    {
        $extendService = GeneralUtility::makeInstance(ExtendService::class);

        // preparing mask
        $source = $extendService->graphicalFunctions->imageCreateFromFile($sourceImagePath);

        $resolutionForExtended = $extendService->resolutionForExtendedImage($sourceImagePath, $direction);

        $maskImage = $extendService->createMask($source, $direction, $resolutionForExtended['width'], $resolutionForExtended['height']);

        // call api
        $array = [
            'image' => curl_file_create($maskImage, 'r'),
            'mask' => curl_file_create($maskImage, 'r'),
            'prompt' => 'outpaint',
            'n' => 1,
            'size' => $resolutionForExtended['width'].'x'.$resolutionForExtended['height'],
        ];

        $openAi = new OpenAi($this->getApiKey());

        $response = $this->validateResponse($openAi->imageEdit($array));

        $images = $this->responseToImages($response);

        if (!(current($images) instanceof Image)) {
            throw new \Exception('Response is not of type Image');
        }

        if ('zoomOut' == $direction) {
            return $images;
        }

        return $extendService->getImages($images, $source, $direction);
    }

    public function validateApiCall(): \stdClass
    {
        $openAi = new OpenAi($this->getApiKey());

        $response = $this->validateResponse($openAi->listModels());

        return $response;
    }

    /**
     * @param string|bool $response
     *
     * @throws \Exception
     */
    public function validateResponse($response): \stdClass
    {
        if (!is_string($response)) {
            throw new \Exception('Response is not string');
        }
        $response = json_decode($response);

        if ($response->error) {
            throw new \Exception($response->error->message);
        }

        return $response;
    }

    /**
     * @return array<Image>
     */
    private function responseToImages(\stdClass $response): array
    {
        $images = [];
        foreach ($response->data as $item) {
            $images[] = GeneralUtility::makeInstance(Image::class, $item->url);
        }

        return $images;
    }

    public function getFolderName(): string
    {
        return 'openai';
    }

    public function getAllowedOperations(): array
    {
        return ['cropAndExtend', 'extend', 'variants', 'filelist', 'saveFile', 'promptResult', 'prompt', 'promptResultAjax'];
    }
}
