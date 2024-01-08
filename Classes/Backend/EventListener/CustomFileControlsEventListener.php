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

namespace DMK\MkContentAi\Backend\EventListener;

use TYPO3\CMS\Backend\Form\Event\CustomFileControlsEvent;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class CustomFileControlsEventListener
{
    /**
     * @var NodeFactory
     */
    public $nodeFactory;

    /**
     * @var IconFactory
     */
    public $iconFactory;

    public function __construct()
    {
        $this->nodeFactory = GeneralUtility::makeInstance(NodeFactory::class);
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
    }

    public function handleEvent(CustomFileControlsEvent $event): void
    {
        $item = ' <div class="form-control-wrap"><button type="button" class="btn btn-default t3js-prompt" id="prompt">';
        $item .= $this->iconFactory->getIcon('actions-image', Icon::SIZE_SMALL)->render().' ';
        $item .= htmlspecialchars('AI generation of image by text prompt');
        $item .= '</button></div>';

        $event->addControl($item);

        $pageRenderer = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Page\PageRenderer::class);
        $pageRenderer->loadRequireJsModule('TYPO3/CMS/Mkcontentai/BackendPrompt');
    }
}
