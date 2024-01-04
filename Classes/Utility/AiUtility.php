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

class AiUtility
{
    public static function getAiAssetId(int $fileUid, ?string $languageIsoCode): string
    {
        return $fileUid.'-'.$languageIsoCode.'-'.self::getSubStringEncryptionKey();
    }

    /**
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    private static function getSubStringEncryptionKey(): string
    {
        $encryptionKey = $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'];

        return substr($encryptionKey, 0, -86);
    }
}
