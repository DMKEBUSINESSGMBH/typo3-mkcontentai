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

namespace DMK\MkContentAi\DTO;

use DMK\MkContentAi\Http\Client\BaseClient;
use DMK\MkContentAi\Utility\AiClientUtility;

class SettingsDTO
{
    private BaseClient $client;

    private bool $validatedApiKey;

    private ?string $userEmail;

    public function __construct(BaseClient $client)
    {
        $this->client = $client;
        $this->validatedApiKey = false;
    }

    public function isValidatedApiKey(): bool
    {
        return $this->validatedApiKey;
    }

    public function setValidatedApiKey(bool $validatedApiKey): void
    {
        $this->validatedApiKey = $validatedApiKey;
    }

    public function getClient(): BaseClient
    {
        return $this->client;
    }

    public function getApiKey(): ?string
    {
        return $this->client->getApiKey();
    }

    public function getMaskedApiKey(): ?string
    {
        return $this->client->getMaskedApiKey();
    }

    public function setApiKey(?string $key): void
    {
        if ($key) {
            $this->client->setApiKey($key);
        }
    }

    public function validateClientApiKey(): bool
    {
        $this->setValidatedApiKey($this->getClient()->validateApiKey());

        return $this->validatedApiKey;
    }

    public function getUserEmail(): ?string
    {
        return $this->userEmail;
    }

    public function setUserEmail(?string $userEmail): void
    {
        $this->userEmail = $userEmail;
    }

    public static function createOpenAiClient(?string $apiKey): SettingsDTO
    {
        $client = AiClientUtility::createOpenAiClient();

        $settingsDto = new SettingsDTO($client);
        $settingsDto->setApiKey($apiKey);

        return $settingsDto;
    }

    public static function createStabilityAiClient(?string $apiKey): SettingsDTO
    {
        $client = AiClientUtility::createStabilityAiClient();
        $settingsDto = new SettingsDTO($client);
        $settingsDto->setApiKey($apiKey);

        return $settingsDto;
    }

    public static function createStableDiffusionClient(?string $apiKey): SettingsDTO
    {
        $client = AiClientUtility::createStableDiffusionClient();
        $settingsDto = new SettingsDTO($client);
        $settingsDto->setApiKey($apiKey);

        return $settingsDto;
    }

    public static function createAltTextClient(?string $apiKey): SettingsDTO
    {
        $client = AiClientUtility::createAltTextClient();
        $settingsDto = new SettingsDTO($client);
        $settingsDto->setApiKey($apiKey);
        $settingsDto->setValidatedApiKey($settingsDto->getClient()->validateApiKey());

        return $settingsDto;
    }

    public static function createOpenAiAltTextClient(?string $apiKey, ?string $model = null): SettingsDTO
    {
        $client = AiClientUtility::createOpenAiAltTextClient();
        $settingsDto = new SettingsDTO($client);
        $settingsDto->setApiKey($apiKey);
        if (null !== $model && '' !== $model) {
            $client->setModel($model);
        }
        $settingsDto->setValidatedApiKey($settingsDto->getClient()->validateApiKey());

        return $settingsDto;
    }

    public static function createSummAiClient(?string $apiKey, ?string $userEmail): SettingsDTO
    {
        $client = AiClientUtility::createSummAiClient();
        $settingsDto = new SettingsDTO($client);
        $settingsDto->setApiKey($apiKey);
        $settingsDto->setUserEmail($userEmail);
        $settingsDto->setValidatedApiKey($settingsDto->getClient()->validateApiKey());

        return $settingsDto;
    }
}
