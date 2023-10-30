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

namespace DMK\MkContentAi\Backend\Hooks;

use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Core\Http\ServerRequestFactory;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ButtonBarHook
{
    /**
     * Retrieves and returns buttons for the button bar.
     *
     * @param array<string, mixed> $params    an array of parameters
     * @param ButtonBar            $buttonBar the ButtonBar instance
     *
     * @return mixed Returns the buttons
     */
    public function getButtons(array $params, ButtonBar $buttonBar)
    {
        $buttons = $params['buttons'];
        $url = $this->buildUriToControllerAction();
        $request = ServerRequestFactory::fromGlobals();
        $currentUri = $request->getUri()->getPath();
        if ('/typo3/module/file/FilelistList' === $currentUri) {
            $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
            $button = $buttonBar->makeLinkButton();
            $button->setShowLabelText(true);
            $button->setIcon($iconFactory->getIcon('actions-image', Icon::SIZE_SMALL));
            $button->setTitle('AI generation of image by text prompt');
            $button->setHref($url);
            $buttons[ButtonBar::BUTTON_POSITION_LEFT][1][] = $button;

            return $buttons;
        }
    }

    public function buildUriToControllerAction(): string
    {
        /**
         * @var UriBuilder $uriBuilder
         */
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $promptUrl = $uriBuilder->buildUriFromRoutePath(
            '/module/system/MkcontentaiContentai',
            [
                'tx_mkcontentai_system_mkcontentaicontentai' => [
                    'action' => 'prompt',
                    'controller' => 'AiImage',
                    'target' => '1:/',
                ],
            ]
        );

        $url = $promptUrl->__toString();

        return $url;
    }
}
