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

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['Backend\Template\Components\ButtonBar']['getButtonsHook']['ButtonBarHook']
    = ButtonBarHook::class.'->getButtons';

$GLOBALS['TYPO3_CONF_VARS']['BE']['ContextMenu']['ItemProviders'][1697195476] =
    DMK\MkContentAi\ContextMenu\ContentAiItemProvider::class;

$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][TYPO3\CMS\Backend\Form\Element\InputTextElement::class] = [
    'className' => DMK\MkContentAi\Backend\Form\Element\InputTextWithAiAltTextSupportElement::class,
];
