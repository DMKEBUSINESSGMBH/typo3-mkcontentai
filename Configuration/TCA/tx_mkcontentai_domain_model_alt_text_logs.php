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

$ll = 'LLL:EXT:mkcontentai/Resources/Private/Language/locallang_db.xlf:';
$versionInformation = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(TYPO3\CMS\Core\Information\Typo3Version::class);

return [
    'ctrl' => [
        'title' => 'LLL:EXT:mkcontentai/Resources/Private/Language/locallang_db.xlf:tx_mkcontentai_domain_model_alt_text_logs',
        'label' => 'sys_file_metadata',
        'type' => 'table_name',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'delete' => 'deleted',
        'hideTable' => true,
    ],
    'columns' => [
        'pid' => [
            'label' => 'pid',
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'alternative' => [
            'exclude' => true,
            'config' => [
                'type' => 'input',
            ],
        ],
        'table_name' => [
            'exclude' => true,
            'config' => [
                'type' => 'input',
            ],
        ],
        'sys_file_metadata' => [
            'exclude' => true,
            'label' => 'LLL:EXT:mkcontentai/Resources/Private/Language/locallang_db.xlf:tx_mkcontentai_domain_model_alt_text_logs.sys_file_metadata',
            'description' => 'LLL:EXT:mkcontentai/Resources/Private/Language/locallang_db.xlf:tx_mkcontentai_domain_model_alt_text_logs.sys_file_metadata.description',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'sys_file_metadata',
                'default' => 0,
                'minitems' => 0,
                'maxitems' => 1,
                'behaviour' => [
                    'localizeChildrenAtParentLocalization' => true,
                    'enableCascadingDelete' => true,
                ],
            ],
        ],
        'crdate' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
    ],
];
