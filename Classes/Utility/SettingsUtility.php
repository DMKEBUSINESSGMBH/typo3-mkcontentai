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

namespace DMK\MkContentAi\Utility;

use DMK\MkContentAi\Controller\AiImageController;
use DMK\MkContentAi\Controller\SettingsController;
use DMK\MkContentAi\Http\Client\AltTextClient;
use DMK\MkContentAi\Http\Client\SummAiClient;
use TYPO3\CMS\Core\Registry;

class SettingsUtility
{
    private Registry $registry;

    public function injectRegistry(Registry $registry): void
    {
        $this->registry = $registry;
    }

    public function isApiKeySetForImageGeneration(): bool
    {
        $imageAiEngine = SettingsController::getImageAiEngine();

        return $this->isApiKeySetForClient(AiImageController::GENERATOR_ENGINE[$imageAiEngine]);
    }

    public function isApiKeySetForAltTextAi(): bool
    {
        return $this->isApiKeySetForClient(AltTextClient::class);
    }

    public function isApiKeySetForSummAi(): bool
    {
        return $this->isApiKeySetForClient(SummAiClient::class);
    }

    private function isApiKeySetForClient(string $client): bool
    {
        return !is_null($this->registry->get($client, 'apiKey'));
    }
}
