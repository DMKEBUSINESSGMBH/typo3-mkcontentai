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
use Symfony\Contracts\HttpClient\ResponseInterface;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Domain\Model\File;

class StableDiffusionClient extends BaseClient implements ClientInterface
{
    private const API_LINK = 'https://stablediffusionapi.com/api/v3/';

    private const DREAMBOOTH_API_LINK = 'https://stablediffusionapi.com/api/v4/dreambooth/';

    public function __construct()
    {
        $this->getApiKey();
    }

    public function validateApiCall(): \stdClass
    {
        $response = $this->request('system_load');

        $response = $this->validateResponse($response->getContent());

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

        if ('processing' == $response->status) {
            $fetchResult = $response->fetch_result;

            while ('processing' == $response->status) {
                sleep(2);
                $response = $this->request('', [], $fetchResult)->getContent();
                if (!is_string($response)) {
                    throw new \Exception('Response is not string');
                }
                $response = json_decode($response);
                sleep(2);
            }
        }

        if (!is_a($response, \stdClass::class)) {
            $response = $this->convertToStdClass($response);
        }

        if (!in_array($response->status, ['ok', 'success']) && !empty($response->status)) {
            $this->throwException($response);
        }

        return $response;
    }

    private function throwException(\stdClass $response): void
    {
        if (is_string($response->messege)) {
            throw new \Exception($response->messege);
        }
        if (is_string($response->message)) {
            throw new \Exception($response->message);
        }
        if (is_iterable($response->messege)) {
            $errors = [];
            foreach ($response->messege as $message) {
                $errors[] = $message[0];
            }
            throw new \Exception(implode(' ', $errors));
        }
    }

    /**
     * @param array<string> $array
     */
    private function convertToStdClass(array $array): \stdClass
    {
        $object = new \stdClass();
        foreach ($array as $key => $value) {
            $object->$key = $value;
        }

        return $object;
    }

    /**
     * @param array<string, float|int|string|null> $queryParams
     *
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    private function request(string $endpoint, array $queryParams = [], string $apiLinkAdjust = ''): ResponseInterface
    {
        $apiLink = self::getApiLink();
        if ('' != $apiLinkAdjust) {
            $apiLink = $apiLinkAdjust;
        }
        $client = HttpClient::create();

        $commonParams = [];
        $commonParams['key'] = $this->getApiKey();
        $commonParams = array_merge($commonParams, $queryParams);

        $response = $client->request(
            'POST',
            $apiLink.$endpoint,
            [
                'body' => $commonParams,
            ]
        );

        return $response;
    }

    public function createImageVariation(File $file): array
    {
        $siteFinder = GeneralUtility::makeInstance(SiteFinder::class);
        $site = current($siteFinder->getAllSites());
        $imageUrl = $file->getOriginalResource()->getPublicUrl();
        if (false != $site) {
            $imageUrl = $site->getBase().$imageUrl;
            if ($this->getCurrentModel()) {
                return $this->dreamboothVariant($imageUrl);
            }

            return $this->stableDiffusionVariant($imageUrl);
        }
        throw new \Exception('Public url for image can not be created)');
    }

    /**
     * @return array<Image>
     */
    private function stableDiffusionVariant(string $imageUrl): array
    {
        $params = [
            'samples' => 3,
            'height' => 1024,
            'width' => 768,
            'prompt' => 'similar',
            'init_image' => $imageUrl,
            'num_inference_steps' => 30,
            'seed' => null,
            'guidance_scale' => 7.5,
            'webhook' => null,
            'track_id' => null,
        ];

        $response = $this->request('img2img', $params);

        $response = $this->validateResponse($response->getContent());

        $images = $this->responseToImages($response);

        return $images;
    }

    /**
     * @return array<Image>
     */
    private function dreamboothVariant(string $imageUrl): array
    {
        $params = [
            'samples' => 3,
            'height' => 1024,
            'width' => 768,
            'prompt' => 'similar',
            'init_image' => $imageUrl,
            'num_inference_steps' => 30,
            'seed' => null,
            'guidance_scale' => 7.5,
            'webhook' => null,
            'track_id' => null,
            'model_id' => $this->getCurrentModel(),
            'scheduler' => 'UniPCMultistepScheduler',
        ];

        $response = $this->request('img2img', $params, self::DREAMBOOTH_API_LINK);

        $response = $this->validateResponse($response->getContent());

        $images = $this->responseToImages($response);

        return $images;
    }

    public function image(string $text): array
    {
        if ($this->getCurrentModel()) {
            return $this->dreamboothImage($text);
        }

        return $this->stableDiffusionImage($text);
    }

    public function upscale(File $file): Image
    {
        throw new \Exception('Not implemented');
    }

    /**
     * @return array<Image>
     */
    public function extend(File $file, string $text = 'Add car'): array
    {
        throw new \Exception('Not implemented');
    }

    /**
     * @return array<Image>
     */
    private function dreamboothImage(string $text): array
    {
        $params = [
            'prompt' => $text,
            'samples' => 3,
            'width' => 1024,
            'height' => 768,
            'num_inference_steps' => 30,
            'seed' => null,
            'guidance_scale' => 7.5,
            'webhook' => null,
            'track_id' => null,
            'model_id' => $this->getCurrentModel(),
        ];
        $response = $this->request('', $params, self::DREAMBOOTH_API_LINK);

        $response = $this->validateResponse($response->getContent());

        $images = $this->responseToImages($response);

        return $images;
    }

    /**
     * @return array<Image>
     */
    private function stableDiffusionImage(string $text): array
    {
        $params = [
            'prompt' => $text,
            'samples' => 3,
            'width' => 1024,
            'height' => 768,
            'num_inference_steps' => 30,
            'seed' => null,
            'guidance_scale' => 7.5,
            'webhook' => null,
            'track_id' => null,
        ];
        $response = $this->request('text2img', $params);

        $response = $this->validateResponse($response->getContent());

        $images = $this->responseToImages($response);

        return $images;
    }

    /**
     * @return array<Image>
     */
    private function responseToImages(\stdClass $response): array
    {
        $images = [];
        foreach ($response->output as $url) {
            $images[] = GeneralUtility::makeInstance(Image::class, $url);
        }

        return $images;
    }

    public function getFolderName(): string
    {
        return 'stablediffusion';
    }

    /**
     * @return array<string>
     */
    public function modelList(): array
    {
        $response = $this->request('model_list', [], 'https://stablediffusionapi.com/api/v4/dreambooth/');

        $response = $this->validateResponse($response->getContent());

        if (is_string(json_encode($response))) {
            return json_decode(json_encode($response), true);
        }

        return [];
    }

    public function getApiLink(): string
    {
        return self::API_LINK;
    }

    public function setCurrentModel(string $modelName): void
    {
        $registry = $this->getRegistry();
        $class = $this->getClass();
        $registry->set($class, 'modelName', $modelName);
    }

    public function getCurrentModel(): string
    {
        $registry = $this->getRegistry();
        $class = $this->getClass();

        return strval($registry->get($class, 'modelName'));
    }

    public function getAllowedOperations(): array
    {
        return ['variants'];
    }
}
