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

use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\Components\ModifyButtonBarEvent;
use TYPO3\CMS\Core\Http\ServerRequestFactory;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ModifyFilelistButtonBarEventListener
{
    /**
     * @var IconFactory
     */
    protected $iconFactory;

    public function __construct()
    {
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
    }

    public function handleEvent(ModifyButtonBarEvent $event): void
    {
        $url = $this->buildUriToControllerAction();
        $buttons = $event->getButtons();
        $request = ServerRequestFactory::fromGlobals();
        $currentUri = $request->getUri()->getPath();

        if ('/typo3/module/file/list' === $currentUri) {
            $icon = $this->iconFactory->getIcon('actions-image', Icon::SIZE_SMALL);
            $buttons[ButtonBar::BUTTON_POSITION_LEFT][1][] = $event->getButtonBar()
                ->makeLinkButton()
                ->setTitle(htmlspecialchars('AI generation of image by text prompt'))
                ->setShowLabelText(true)
                ->setIcon($icon)
                ->setHref($url)
                ->setClasses('btn btn-default');
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
