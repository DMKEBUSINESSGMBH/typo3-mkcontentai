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

use DMK\MkContentAi\Utility\PermissionsUtility;
use DMK\MkContentAi\Utility\SettingsUtility;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\Components\ModifyButtonBarEvent;
use TYPO3\CMS\Core\Http\ServerRequestFactory;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

class ModifyFilelistButtonBarEventListener
{
    /**
     * @var IconFactory
     */
    protected $iconFactory;

    private PermissionsUtility $permissionsUtility;

    private SettingsUtility $settingsUtility;

    public function __construct(PermissionsUtility $permissionsUtility, SettingsUtility $settingsUtility)
    {
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $this->permissionsUtility = $permissionsUtility;
        $this->settingsUtility = $settingsUtility;
    }

    public function handleEvent(ModifyButtonBarEvent $event): void
    {
        if (!$this->permissionsUtility->userHasAccessToImageGenerationPromptButton()) {
            return;
        }
        if (!$this->settingsUtility->isApiKeySetForImageGeneration()) {
            return;
        }

        $translatedMessage = LocalizationUtility::translate('labelAiGenerateText', 'mkcontentai') ?? '';
        $url = $this->buildUriToControllerAction();
        $buttons = $event->getButtons();
        $request = ServerRequestFactory::fromGlobals();
        $currentUri = $request->getUri()->getPath();
        $iconSize = 'small';

        if ('/typo3/module/file/list' === $currentUri) {
            $icon = $this->iconFactory->getIcon('actions-image', $iconSize);
            $buttons[ButtonBar::BUTTON_POSITION_LEFT][1][] = $event->getButtonBar()
                ->makeLinkButton()
                ->setTitle(htmlspecialchars($translatedMessage))
                ->setShowLabelText(true)
                ->setIcon($icon)
                ->setHref($url)
                ->setClasses('btn btn-primary');
            $event->setButtons($buttons);
        }
    }

    public function buildUriToControllerAction(): string
    {
        $backendUriBuilder = GeneralUtility::makeInstance(UriBuilder::class);

        $uriParameters = [
            'controller' => 'AiImage',
            'action' => 'prompt',
            'extensionName' => 'mkcontentai',
            'target' => '1:/',
            'currentPage' => 1,
        ];

        $generateAiImageUri = $backendUriBuilder->buildUriFromRoutePath('/module/mkcontentai/AiImage/prompt', $uriParameters);
        $url = $generateAiImageUri->__toString();

        return $url;
    }
}
