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

namespace DMK\MkContentAi\Http\Client;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Domain\Model\File;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

class OpenAiAltTextClient extends BaseClient implements ClientInterface
{
    protected string $clientName = 'openai_alttext';

    private const MODEL_KEY = 'altTextModel';
    private const DEFAULT_MODEL = 'gpt-4o-mini';

    private HttpClientInterface $client;

    public function __construct()
    {
        $this->client = HttpClient::create();
    }

    /**
     *
     * @throws \Exception
     */
    public function getAltTextForFile(File $file, ?string $languageIsoCode = null): string
    {
        $this->validate();

        $localFile = $file->getOriginalResource()->getForLocalProcessing();
        $mimeType = mime_content_type($localFile) ?: 'image/jpeg';
        $imageData = base64_encode((string) file_get_contents($localFile));
        $dataUrl = 'data:' . $mimeType . ';base64,' . $imageData;

        $langHint = '';
        if (null !== $languageIsoCode) {
            $langHint = ' Write the alt text in the language with ISO code "' . $languageIsoCode . '".';
        }

        $payload = [
            'model'      => $this->getModel(),
            'max_tokens' => 300,
            'messages'   => [
                [
                    'role'    => 'user',
                    'content' => [
                        [
                            'type'      => 'image_url',
                            'image_url' => [
                                'url'    => $dataUrl,
                                'detail' => 'low',
                            ],
                        ],
                        [
                            'type' => 'text',
                            'text' => 'Generate a concise, descriptive alt text for this image suitable for web accessibility. Return only the alt text, no extra explanation, no quotes.' . $langHint,
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->client->request('POST', 'https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->getApiKey(),
                'Content-Type'  => 'application/json',
            ],
            'body' => json_encode($payload),
        ]);

        $statusCode = $response->getStatusCode();
        $data = json_decode($response->getContent(false), true);

        if (200 !== $statusCode) {
            $errorMessage = $data['error']['message'] ?? 'Unknown OpenAI API error';
            throw new \Exception('OpenAI API error: ' . $errorMessage, $statusCode);
        }

        $altText = trim($data['choices'][0]['message']['content'] ?? '');

        if (empty($altText)) {
            $translatedMessage = LocalizationUtility::translate('labelResponseNotString', 'mkcontentai') ?? 'Empty response from OpenAI';
            throw new \Exception($translatedMessage);
        }

        return $altText;
    }

    /**
     * OpenAI has no asset-ID cache like alttext.ai.
     * Always throw 404 so AiAltTextService falls through to getAltTextForFile(),
     * which is the same path it already uses for alttext.ai cache misses.
     *
     * @throws \Exception
     */
    public function getByAssetId(int $assetId, ?string $languageIsoCode = null): string
    {
        throw new \Exception('No cached alt text available', 404);
    }

    /**
     * @throws \Exception
     */
    public function getTestApiCall(): \stdClass
    {
        $response = $this->client->request('GET', 'https://api.openai.com/v1/models', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->getApiKey(),
            ],
        ]);

        $data = json_decode($response->getContent(false), true);

        if (200 !== $response->getStatusCode()) {
            $errorMessage = $data['error']['message'] ?? 'Invalid API key';
            throw new \Exception('OpenAI API error: ' . $errorMessage, $response->getStatusCode());
        }

        return (object) $data;
    }

    public function getModel(): string
    {
        $registry = GeneralUtility::makeInstance(Registry::class);
        $model = $registry->get(get_class($this), self::MODEL_KEY);

        return is_string($model) && '' !== $model ? $model : self::DEFAULT_MODEL;
    }

    public function setModel(string $model): void
    {
        $registry = GeneralUtility::makeInstance(Registry::class);
        $registry->set(get_class($this), self::MODEL_KEY, $model);
    }
}
