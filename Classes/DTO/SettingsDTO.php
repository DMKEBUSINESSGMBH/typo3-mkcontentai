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

/**
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
class SettingsDTO
{
    private BaseClient $client;

    private bool $validatedApiKey;

    private ?string $userEmail;

    /**
     * @var list<string>|null
     */
    private ?array $newsContentTypes;

    /**
     * @var list<string>|null
     */
    private ?array $availableNewsContentTypes;

    private ?int $summAiAppendedContentUid;

    private ?bool $summAiDevMode;

    private ?bool $summAiDisclaimer;

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

    /**
     * @param list<string>|null $newsContentTypes
     * @param list<string>|null $availableNewsContentTypes
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public static function createSummAiClient(
        ?string $apiKey,
        ?string $userEmail,
        ?array $newsContentTypes,
        ?array $availableNewsContentTypes,
        ?int $appendedContentUid,
        ?bool $summAiDevMode,
        ?bool $summAiDisclaimer
    ): SettingsDTO {
        $client = AiClientUtility::createSummAiClient();
        $settingsDto = new SettingsDTO($client);
        $settingsDto->setApiKey($apiKey);
        $settingsDto->setUserEmail($userEmail);
        $settingsDto->setValidatedApiKey($settingsDto->getClient()->validateApiKey());
        $settingsDto->setNewsContentTypes($newsContentTypes);
        $settingsDto->setAvailableNewsContentTypes($availableNewsContentTypes);
        $settingsDto->setSummAiAppendedContentUid($appendedContentUid);
        $settingsDto->setSummAiDevMode($summAiDevMode);
        $settingsDto->setSummAiDisclaimer($summAiDisclaimer);

        return $settingsDto;
    }

    /**
     * @return list<string>|null
     */
    public function getNewsContentTypes(): ?array
    {
        return $this->newsContentTypes;
    }

    /**
     * @param list<string>|null $newsContentTypes
     */
    public function setNewsContentTypes(?array $newsContentTypes): void
    {
        $this->newsContentTypes = $newsContentTypes;
    }

    /**
     * @return list<string>|null
     */
    public function getAvailableNewsContentTypes(): ?array
    {
        return $this->availableNewsContentTypes;
    }

    /**
     * @param list<string>|null $availableNewsContentTypes
     */
    public function setAvailableNewsContentTypes(?array $availableNewsContentTypes): void
    {
        $this->availableNewsContentTypes = $availableNewsContentTypes;
    }

    public function getSummAiAppendedContentUid(): ?int
    {
        return $this->summAiAppendedContentUid;
    }

    public function setSummAiAppendedContentUid(?int $summAiAppendedContentUid): void
    {
        $this->summAiAppendedContentUid = $summAiAppendedContentUid;
    }

    public function getSummAiDevMode(): ?bool
    {
        return $this->summAiDevMode;
    }

    public function setSummAiDevMode(?bool $summAiDevMode): void
    {
        $this->summAiDevMode = $summAiDevMode;
    }

    public function getSummAiDisclaimer(): ?bool
    {
        return $this->summAiDisclaimer;
    }

    public function setSummAiDisclaimer(?bool $summAiDisclaimer): void
    {
        $this->summAiDisclaimer = $summAiDisclaimer;
    }
}
