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

namespace DMK\MkContentAi\Utility;

use DMK\MkContentAi\Http\Client\AltTextClient;
use DMK\MkContentAi\Http\Client\OpenAiClient;
use DMK\MkContentAi\Http\Client\StabilityAiClient;
use DMK\MkContentAi\Http\Client\StableDiffusionClient;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class AiClientUtility
{
    public static function createOpenAiClient(): OpenAiClient
    {
        return GeneralUtility::makeInstance(OpenAiClient::class);
    }

    public static function createStableDiffusionClient(): StableDiffusionClient
    {
        return GeneralUtility::makeInstance(StableDiffusionClient::class);
    }

    public static function createStabilityAiClient(): StabilityAiClient
    {
        return GeneralUtility::makeInstance(StabilityAiClient::class);
    }

    public static function createAltTextClient(): AltTextClient
    {
        return GeneralUtility::makeInstance(AltTextClient::class);
    }
}
