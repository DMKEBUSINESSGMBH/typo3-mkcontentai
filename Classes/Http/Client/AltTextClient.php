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

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\Multipart\FormDataPart;
use TYPO3\CMS\Extbase\Domain\Model\File;

class AltTextClient extends BaseClient implements ClientInterface
{
    /**
     * @var \Symfony\Contracts\HttpClient\HttpClientInterface
     */
    private $client;

    public function __construct()
    {
        $this->client = HttpClient::create();
    }

    private function getAuthorizationHeader(): array
    {
        return [
            'X-API-Key' => $this->getApiKey(),
        ];
    }

    public function getAltTextForFile(File $file): string
    {
        $localFile = $file->getOriginalResource()->getForLocalProcessing();

        $formFields = [
            'image[raw]' => DataPart::fromPath($localFile),
            'image[asset_id]' => (string) $file->getOriginalResource()->getUid(),
        ];
        $formData = new FormDataPart($formFields);

        $headers = array_merge($this->getAuthorizationHeader(), $formData->getPreparedHeaders()->toArray());

        $response = $this->client->request('POST', 'https://alttext.ai/api/v1/images', [
            'headers' => $headers,
            'body' => $formData->bodyToIterable(),
        ]);

        $response = $this->validateResponse($response->getContent());

        return $response->alt_text;
    }

    public function getByAssetId(int $assetId): string
    {
        $response = $this->client->request('GET', 'https://alttext.ai/api/v1/images/'.$assetId, [
            'headers' => $this->getAuthorizationHeader(),
        ]);

        $response = $this->validateResponse($response->getContent());

        return $response->alt_text;
    }

    public function getAccount(): void
    {
        $response = $this->client->request('GET', 'https://alttext.ai/api/v1/account', [
            'headers' => $this->getAuthorizationHeader(),
        ]);

        $response = $this->validateResponse($response->getContent());
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

        return $response;
    }

    public function validateApiCall(): \stdClass
    {
        $response = $this->validateResponse($this->getAccount());

        return $response;
    }
}
