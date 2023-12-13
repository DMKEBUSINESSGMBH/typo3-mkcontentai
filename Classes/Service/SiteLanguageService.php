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

use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class SiteLanguageService
{
    private const SELECTED_LANGUAGE = 'language';

    private Registry $registry;

    private SiteFinder $siteFinder;

    public function __construct()
    {
        $this->registry = GeneralUtility::makeInstance(Registry::class);
        $this->siteFinder = GeneralUtility::makeInstance(SiteFinder::class);
    }

    public function getLanguage(): ?string
    {
        return $this->registry->get($this->getClass(), self::SELECTED_LANGUAGE);
    }

    public function setLanguage(string $language): void
    {
        $this->registry->set($this->getClass(), self::SELECTED_LANGUAGE, $language);
    }

    /**
     * @return array<string, string>
     */
    public function getAllAvailableLanguages(): array
    {
        $allSites = $this->siteFinder->getAllSites();
        $languageCode = [];

        foreach ($allSites as $site) {
            $siteLanguages = $site->getAllLanguages();

            foreach ($siteLanguages as $siteLanguage) {
                /** @var array<string, string> $language */
                $language = $siteLanguage->toArray();
                $languageCode[$language['twoLetterIsoCode']] = $language['title'];
            }
        }

        return $languageCode;
    }

    protected function getClass(): string
    {
        return get_class($this);
    }
}
