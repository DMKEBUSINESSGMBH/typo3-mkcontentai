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

defined('TYPO3') || exit;

if (TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('news')) {
    TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns(
        'tx_news_domain_model_news',
        [
            'tx_mkcontentai_original_news' => [
                'exclude' => 0,
                'label' => 'LLL:EXT:mkcontentai/Resources/Private/Language/locallang_db.xlf:labelOriginalUidFieldTitle',
                'description' => 'LLL:EXT:mkcontentai/Resources/Private/Language/locallang_db.xlf:labelOriginalUidFieldDescription',
                'config' => [
                    'type' => 'select',
                    'renderType' => 'selectSingle',
                    'foreign_table' => 'tx_news_domain_model_news',
                    'items' => [
                        ['', 0],
                    ],
                    'maxitems' => 1,
                    'size' => 1,
                    'readOnly' => true,
                    'default' => 0,
                ],
            ],
            'tx_mkcontentai_translated_news' => [
                'exclude' => 0,
                'label' => 'LLL:EXT:mkcontentai/Resources/Private/Language/locallang_db.xlf:labelTranslatedUidFieldTitle',
                'description' => 'LLL:EXT:mkcontentai/Resources/Private/Language/locallang_db.xlf:labelTranslatedUidFieldDescription',
                'config' => [
                    'type' => 'select',
                    'renderType' => 'selectSingle',
                    'foreign_table' => 'tx_news_domain_model_news',
                    'items' => [
                        ['', 0],
                    ],
                    'maxitems' => 1,
                    'size' => 1,
                    'readOnly' => true,
                    'default' => 0,
                ],
            ],
        ]
    );

    $newFields = 'tx_mkcontentai_original_news, tx_mkcontentai_translated_news';
    $position = '--div--;MK Content AI,'.$newFields;

    TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('tx_news_domain_model_news', $position);
}
