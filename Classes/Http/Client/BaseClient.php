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

use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class BaseClient
{
    private const API_KEY = 'apiKey';

    /**
     * @throws \Exception
     */
    public function validate(): void
    {
        $apiKey = $this->getApiKey();
        if (empty($apiKey)) {
            throw new \Exception('OpenAI API KEY not settled.');
        }
    }

    public function getApiKey(): string
    {
        $registry = $this->getRegistry();
        $class = $this->getClass();

        return strval($registry->get($class, self::API_KEY));
    }

    public function getMaskedApiKey(): string
    {
        $apiKey = $this->getApiKey();
        $length = strlen($apiKey);
        $charsCount = 5;

        if ($length > $charsCount * 2) {
            return substr($apiKey, 0, $charsCount).str_repeat('*', $length - $charsCount * 2).substr($apiKey, -$charsCount, $charsCount);
        }
        if ($length) {
            return $apiKey;
        }

        return '';
    }

    public function setApiKey(string $apiKey): void
    {
        $registry = $this->getRegistry();
        $class = $this->getClass();
        $registry->set($class, self::API_KEY, $apiKey);
    }

    protected function getRegistry(): Registry
    {
        return GeneralUtility::makeInstance(Registry::class);
    }

    protected function getClass(): string
    {
        return get_class($this);
    }
}
