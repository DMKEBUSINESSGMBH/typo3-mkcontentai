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

defined('TYPO3') or exit;

(static function () {
    TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
        'Mkcontentai',
        'system',
        'contentai',
        '',
        [
            DMK\MkContentAi\Controller\AiImageController::class => 'filelist, variants, prompt, promptResult, saveFile, upscale, extend, cropAndExtend, crop',
            DMK\MkContentAi\Controller\SettingsController::class => 'settings',
            DMK\MkContentAi\Controller\AiTextController::class => 'altText, altTextSave, altTexts, altTextsSave, filelist',
            DMK\MkContentAi\Controller\AiVideoController::class => 'prepareImageToVideo, imageToVideo, filelist, saveFile',
        ],
        [
            'access' => 'user,group',
            'icon' => 'EXT:mkcontentai/Resources/Public/Icons/Extension.svg',
            'labels' => 'LLL:EXT:mkcontentai/Resources/Private/Language/locallang_contentai.xlf',
        ]
    );
})();

$GLOBALS['TYPO3_CONF_VARS']['BE']['customPermOptions']['mkcontentaiSettingsPermissions'] = [
    'header' => 'LLL:EXT:mkcontentai/Resources/Private/Language/locallang_contentai.xlf:labelMkcontentaiPermissions',
    'items' => [
        'settingsPermissions' => [
            'LLL:EXT:mkcontentai/Resources/Private/Language/locallang_contentai.xlf:labelSettingsPageCurrentGroup',
        ],
        'tt_contentImagePrompt' => [
            'LLL:EXT:mkcontentai/Resources/Private/Language/locallang_contentai.xlf:labelSettingsImageGenerationPrompt',
        ],
    ],
];
