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

namespace DMK\MkContentAi\Service;

use DMK\MkContentAi\Http\Client\AltTextClient;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Domain\Model\File;

class AiAltTextService
{
    public AltTextClient $altTextClient;

    public function __construct()
    {
        /** @var AltTextClient $altTextClient */
        $altTextClient = GeneralUtility::makeInstance(AltTextClient::class);
        $this->altTextClient = $altTextClient;
    }

    /**
     * @throws \Exception
     */
    public function getAltText(File $file, string $languageIsoCode = null): string
    {
        try {
            $altText = $this->altTextClient->getByAssetId($file->getOriginalResource()->getUid(), $languageIsoCode);
        } catch (\Exception $e) {
            if (404 != $e->getCode()) {
                throw new \Exception($e->getMessage());
            }

            return $this->altTextClient->getAltTextForFile($file, $languageIsoCode);
        }

        return $altText;
    }
}
