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

/**
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class SettingsRequestDTO
{
    protected ?string $openAiApiKeyValue = null;

    /**
     * @var array<array<string, string>|string>|null
     */
    protected ?array $stableDiffusionValues = [];
    protected ?string $stabilityAiApiValue = null;
    protected ?string $stableDiffusionAiApiValue = null;
    protected ?string $altTextAiApiValue = null;
    protected ?string $summAiApiValue = null;
    protected ?int $imageAiEngine = 0;
    protected ?string $summAiUserEmail = null;
    protected ?string $selectedAltTextAiLanguage = null;
    protected ?string $selectedSdModel = null;

    /**
     * @var list<string>|null
     */
    protected ?array $newsContentTypes = null;

    /**
     * @var list<string>|null
     */
    protected ?array $availableNewsContentTypes = null;
    protected ?int $summAiAppendedContentUid = null;
    protected ?bool $summAiDevMode = null;
    protected ?bool $summAiDisclaimer = null;

    /**
     * @var array<string, string>|null
     */
    protected ?array $altTextAiLanguage = null;

    public static function empty(): self
    {
        return new self();
    }

    public function getOpenAiApiKeyValue(): ?string
    {
        return $this->openAiApiKeyValue;
    }

    public function setOpenAiApiKeyValue(?string $openAiApiKeyValue): void
    {
        $this->openAiApiKeyValue = $openAiApiKeyValue;
    }

    /**
     * @return array<array<string, string>|string>|null
     */
    public function getStableDiffusionValues(): ?array
    {
        return $this->stableDiffusionValues;
    }

    /**
     * @param array<array<string, string>|string>|null $stableDiffusionValues
     */
    public function setStableDiffusionValues(?array $stableDiffusionValues): void
    {
        $this->stableDiffusionValues = $stableDiffusionValues;
    }

    public function getStabilityAiApiValue(): ?string
    {
        return $this->stabilityAiApiValue;
    }

    public function setStabilityAiApiValue(?string $stabilityAiApiValue): void
    {
        $this->stabilityAiApiValue = $stabilityAiApiValue;
    }

    public function getAltTextAiApiValue(): ?string
    {
        return $this->altTextAiApiValue;
    }

    public function setAltTextAiApiValue(?string $altTextAiApiValue): void
    {
        $this->altTextAiApiValue = $altTextAiApiValue;
    }

    public function getSummAiApiValue(): ?string
    {
        return $this->summAiApiValue;
    }

    public function setSummAiApiValue(?string $summAiApiValue): void
    {
        $this->summAiApiValue = $summAiApiValue;
    }

    public function getImageAiEngine(): ?int
    {
        return $this->imageAiEngine;
    }

    public function setImageAiEngine(?int $imageAiEngine): void
    {
        $this->imageAiEngine = $imageAiEngine;
    }

    public function getSummAiUserEmail(): ?string
    {
        return $this->summAiUserEmail;
    }

    public function setSummAiUserEmail(?string $summAiUserEmail): void
    {
        $this->summAiUserEmail = $summAiUserEmail;
    }

    /**
     * @return array<string, string>|null
     */
    public function getAltTextAiLanguage(): ?array
    {
        return $this->altTextAiLanguage;
    }

    /** @param array<string, string> $altTextAiLanguage */
    public function setAltTextAiLanguage(array $altTextAiLanguage): void
    {
        $this->altTextAiLanguage = $altTextAiLanguage;
    }

    public function getSelectedAltTextAiLanguage(): ?string
    {
        return $this->selectedAltTextAiLanguage;
    }

    public function setSelectedAltTextAiLanguage(?string $selectedAltTextAiLanguage): void
    {
        $this->selectedAltTextAiLanguage = $selectedAltTextAiLanguage;
    }

    public function getStableDiffusionAiApiValue(): ?string
    {
        return $this->stableDiffusionAiApiValue;
    }

    public function setStableDiffusionAiApiValue(?string $stableDiffusionAiApiValue): void
    {
        $this->stableDiffusionAiApiValue = $stableDiffusionAiApiValue;
    }

    public function getSelectedStableDiffusionModel(): ?string
    {
        return $this->selectedSdModel;
    }

    public function setSelectedSdModel(?string $selectedSdModel): void
    {
        $this->selectedSdModel = $selectedSdModel;
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
