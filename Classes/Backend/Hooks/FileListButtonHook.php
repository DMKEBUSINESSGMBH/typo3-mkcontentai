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

use DMK\MkContentAi\Backend\Template\Components\Buttons\CustomButton;
use DMK\MkContentAi\ContextMenu\ContentAiItemProvider;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Http\ServerRequestFactory;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Filelist\FileListEditIconHookInterface;

class FileListButtonHook implements FileListEditIconHookInterface
{
    protected ContentAiItemProvider $contentAiItemProvider;

    public function __construct()
    {
        $this->contentAiItemProvider = GeneralUtility::makeInstance(ContentAiItemProvider::class, '', '');
    }

    /**
     * @SuppressWarnings("UnusedFormalParameter")
     *
     * @param string[]                     &$cells
     * @param \TYPO3\CMS\Filelist\FileList &$parentObject
     */
    public function manipulateEditIcons(&$cells, &$parentObject): void
    {
        $request = ServerRequestFactory::fromGlobals();
        $resource = $cells['__fileOrFolderObject'];

        if (!$resource instanceof File) {
            return;
        }

        foreach ($this->contentAiItemProvider->getItemsConfiguration() as $actionName) {
            $route = $request->getQueryParams()['route'] ?? null;
            $currentUri = $request->getUri()->getPath();

            if ('/typo3/module/file/FilelistList' === $currentUri || '/module/file/FilelistList' === $route) {
                ('alt' === $actionName['callbackAction'])
                    ? $url = $this->buildUriToControllerAction($resource, $this->mapAction($actionName['callbackAction']), 'AiText')
                    : $url = $this->buildUriToControllerAction($resource, $this->mapAction($actionName['callbackAction']), 'AiImage');

                $htmlCode = '<a href="'.$url.'" class="btn btn-default" title="'.(LocalizationUtility::translate($actionName['label']) ?? '').'"><span class="t3js-icon icon icon-size-small icon-state-default">
	<span class="icon-markup">
	<svg class="icon-color"><use xlink:href="/typo3/sysext/core/Resources/Public/Icons/T3Icons/sprites/actions.svg#actions-rocket" /></svg>
</span></span></a>';
                $button = new CustomButton();
                $cells[$actionName['callbackAction']] = $button->setHtmlSource($htmlCode);
            }
        }
    }

    public function buildUriToControllerAction(FileInterface $file, string $actionName, string $controllerName): string
    {
        /**
         * @var UriBuilder $uriBuilder
         */
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $promptUrl = $uriBuilder->buildUriFromRoutePath(
            '/module/system/MkcontentaiContentai',
            [
                'tx_mkcontentai_system_mkcontentaicontentai' => [
                    'action' => $actionName,
                    'controller' => $controllerName,
                    'file' => $file->getStorage()->getUid().':'.$file->getIdentifier(),
                ],
            ]
        );

        $url = $promptUrl->__toString();

        return $url;
    }

    public function mapAction(string $actionName): string
    {
        if ('extend' === $actionName) {
            return 'cropAndExtend';
        }

        if ('alt' === $actionName) {
            return 'altText';
        }

        return $actionName;
    }
}
