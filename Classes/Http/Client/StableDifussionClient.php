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

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\ResponseInterface;

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

        if ('error' == $response->status) {
            throw new \Exception($response->message);
        }

        return $response;
    }

    /**
     * @param array<string> $queryParams
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
}
