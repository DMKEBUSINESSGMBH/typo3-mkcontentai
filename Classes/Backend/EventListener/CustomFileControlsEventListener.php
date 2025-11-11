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

namespace DMK\MkContentAi\Backend\EventListener;

use DMK\MkContentAi\Utility\PermissionsUtility;
use DMK\MkContentAi\Utility\SettingsUtility;
use TYPO3\CMS\Backend\Form\Event\CustomFileControlsEvent;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

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

    private PermissionsUtility $permissionsUtility;

    private SettingsUtility $settingsUtility;

    public function __construct(PermissionsUtility $permissionsUtility, SettingsUtility $settingsUtility)
    {
        $this->nodeFactory = GeneralUtility::makeInstance(NodeFactory::class);
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $this->permissionsUtility = $permissionsUtility;
        $this->settingsUtility = $settingsUtility;
    }

    public function handleEvent(CustomFileControlsEvent $event): void
    {
        if (!$this->permissionsUtility->userHasAccessToImageGenerationPromptButton()) {
            return;
        }
        if (!$this->settingsUtility->isApiKeySetForImageGeneration()) {
            return;
        }

        $iconSize = 'small';
        $translatedMessage = LocalizationUtility::translate('labelAiGenerateText', 'mkcontentai') ?? '';
        $item = ' <div class="form-control-wrap"><button type="button" class="btn btn-primary t3js-prompt" id="prompt">';
        $item .= $this->iconFactory->getIcon('actions-image', $iconSize)->render().' ';
        $item .= htmlspecialchars($translatedMessage);
        $item .= '</button></div>';

        $event->addControl($item);

        $pageRenderer = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Page\PageRenderer::class);

        $pageRenderer->loadJavaScriptModule('@t3docs/mkcontentai/BackendPrompt.js');
    }
}
