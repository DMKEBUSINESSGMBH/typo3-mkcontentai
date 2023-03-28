<?php

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

namespace DMK\MkContentAi\Http\Client;

use DMK\MkContentAi\Domain\Model\Image;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\ResponseInterface;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Domain\Model\File;

class StableDifussionClient extends BaseClient implements ClientInterface
{
    private const API_LINK = 'https://stablediffusionapi.com/api/v3/';

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

        if (!in_array($response->status, ['ok', 'success'])) {
            if (is_string($response->messege)) {
                throw new \Exception($response->messege);
            }
            if (is_a($response->messege, \stdClass::class) && is_iterable($response->messege)) {
                $errors = [];
                foreach ($response->messege as $message) {
                    $errors[] = $message[0];
                }
                throw new \Exception(implode(' ', $errors));
            }
        }

        return $response;
    }

    /**
     * @param array<string, string|int|null> $queryParams
     *
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    private function request(string $endpoint, array $queryParams = []): ResponseInterface
    {
        $client = HttpClient::create();

        $commonParams = [];
        $commonParams['key'] = $this->getApiKey();
        $commonParams = array_merge($commonParams, $queryParams);

        $response = $client->request(
            'POST',
            self::API_LINK . $endpoint,
            [
                'query' => $commonParams,
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
            $imageUrl = $site->getBase() . $imageUrl;
        }

        $params = [
            'samples' => 1,
            'height' => 256,
            'width' => 256,
            'prompt' => 'similar',
            'init_image' => $imageUrl,
        ];

        $response = $this->request('img2img', $params);

        $response = $this->validateResponse($response->getContent());

        $images = $this->responseToImages($response);

        return $images;
    }

    public function image(string $text): array
    {
        $params = [
            'prompt' => $text,
            'samples' => 1,
            'width' => 256,
            'height' => 256,
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
}
