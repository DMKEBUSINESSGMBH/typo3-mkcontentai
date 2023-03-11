<?php

/*
 * Copyright notice
 *
 * (c) DMK E-BUSINESS GmbH <dev@dmk-ebusiness.de>
 * All rights reserved
 *
 * This file is part of TYPO3 CMS-based extension "container" by b13.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

if (!defined('TYPO3_MODE')) {
    exit('Access denied.');
}

(static function () {
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
        'Mkcontentai',
        'web',
        'contentai',
        '',
        [
            \DMK\MkContentAi\Controller\ImageController::class => 'list, chooseForVariant, uploadForVariant, generateImagePrompt, chooseGeneratedImage, saveFile',
        ],
        [
            'access' => 'user,group',
            'icon' => 'EXT:mkcontentai/Resources/Public/Icons/user_mod_contentai.svg',
            'labels' => 'LLL:EXT:mkcontentai/Resources/Private/Language/locallang_contentai.xlf',
        ]
    );

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_mkcontentai_domain_model_image', 'EXT:mkcontentai/Resources/Private/Language/locallang_csh_tx_mkcontentai_domain_model_image.xlf');
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_mkcontentai_domain_model_image');
})();
