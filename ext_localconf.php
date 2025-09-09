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

use DMK\MkContentAi\Backend\Hooks\ButtonBarHook;
use TYPO3\CMS\Core\Utility\GeneralUtility;

// Hook into the button bar (only for TYPO3 ^11 version)
$typo3Version = GeneralUtility::makeInstance(TYPO3\CMS\Core\Information\Typo3Version::class);

if (11 == $typo3Version->getMajorVersion()) {
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['Backend\Template\Components\ButtonBar']['getButtonsHook']['ButtonBarHook']
    = ButtonBarHook::class.'->getButtons';
}

$GLOBALS['TYPO3_CONF_VARS']['BE']['ContextMenu']['ItemProviders'][1697195476] =
    DMK\MkContentAi\ContextMenu\ContentAiItemProvider::class;

$GLOBALS['TYPO3_CONF_VARS']['BE']['ContextMenu']['ItemProviders'][1697195577] =
    DMK\MkContentAi\ContextMenu\ContentAiPageProvider::class;

$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][TYPO3\CMS\Backend\Form\Element\InputTextElement::class] = [
    'className' => DMK\MkContentAi\Backend\Form\Element\InputTextWithAiAltTextSupportElement::class,
];

$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][TYPO3\CMS\Core\Resource\Event\BeforeFileDeletedEvent::class] = [
    'className' => DMK\MkContentAi\Backend\EventListener\FileEventListener::class,
];

// Register hooks
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass'][] =
    DMK\MkContentAi\Backend\Hooks\CustomDataHandler::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] =
    DMK\MkContentAi\Backend\Hooks\CustomDataHandler::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList::class]['modifyQuery'][] =
    DMK\MkContentAi\Backend\Hooks\AddColumnsToNewsQueryInRecordListHook::class;

// Register extended EXT:news model
$GLOBALS['TYPO3_CONF_VARS']['EXT']['news']['classes']['Domain/Model/News']['mkcontentai'] = 'mkcontentai';
