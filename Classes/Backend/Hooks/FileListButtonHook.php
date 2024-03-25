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
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Filelist\FileListEditIconHookInterface;

class FileListButtonHook implements FileListEditIconHookInterface
{
    protected ContentAiItemProvider $contentAiItemProvider;
    protected UriBuilder $uriBuilder;

    public function __construct()
    {
        $this->contentAiItemProvider = GeneralUtility::makeInstance(ContentAiItemProvider::class, '', '');
        $this->uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
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

        /** @var string $route */
        $route = $request->getQueryParams()['route'] ?? '';
        $currentUri = $request->getUri()->getPath();

        if (!$this->isFileListAction($currentUri, $route)) {
            return;
        }

        $resource = null;
        $actions = [];

        if ($cells['__fileOrFolderObject'] instanceof File) {
            $resource = $cells['__fileOrFolderObject'];
            $actions = $this->contentAiItemProvider->getItemsConfiguration();
            $this->contentAiItemProvider->setContext('sys_file', $this->buildFileIdentifier($resource));
        }

        if ($cells['__fileOrFolderObject'] instanceof Folder) {
            $resource = $cells['__fileOrFolderObject'];
            $actions = $this->contentAiItemProvider->getItemsConfigurationForFolder();
            $this->contentAiItemProvider->setContext('sys_file_storage', $this->buildFolderIdentifier($resource));
        }

        /**
         * @var string $key
         */
        foreach ($actions as $key => $actionName) {
            if (!$this->canRenderButton($key, $actionName['type'])) {
                continue;
            }

            $targetControllerClassName = $this->determineTargetControllerClassName($actionName['callbackAction']);
            $url = $this->generateUrl($resource, $actionName['callbackAction'], $targetControllerClassName);
            $cells[$actionName['callbackAction']] =
                (new CustomButton())->setHtmlSource($this->generateHtmlButton($url, $actionName['label']));
        }
    }

    private function buildUriToControllerActionFile(File $file, string $actionName, string $controllerName): string
    {
        $promptUrl = $this->uriBuilder->buildUriFromRoutePath(
            '/module/system/MkcontentaiContentai',
            [
                'tx_mkcontentai_system_mkcontentaicontentai' => [
                    'action' => $actionName,
                    'controller' => $controllerName,
                    'file' => $this->buildFileIdentifier($file),
                ],
            ]
        );

        return $promptUrl->__toString();
    }

    private function buildUriToControllerActionFolder(Folder $folder, string $actionName, string $controllerName): string
    {
        $promptUrl = $this->uriBuilder->buildUriFromRoutePath(
            '/module/system/MkcontentaiContentai',
            [
                'tx_mkcontentai_system_mkcontentaicontentai' => [
                    'action' => $actionName,
                    'controller' => $controllerName,
                    'folderName' => $this->buildFolderIdentifier($folder),
                ],
            ]
        );

        return $promptUrl->__toString();
    }

    private function mapAction(string $actionName): string
    {
        if ('extend' === $actionName) {
            return 'cropAndExtend';
        }

        if ('alt' === $actionName) {
            return 'altText';
        }

        return $actionName;
    }

    private function buildFileIdentifier(FileInterface $file): string
    {
        return $file->getStorage()->getUid().':'.$file->getIdentifier();
    }

    private function buildFolderIdentifier(Folder $folder): string
    {
        return $folder->getCombinedIdentifier();
    }

    private function isFileListAction(string $currentUri, string $route): bool
    {
        return '/typo3/module/file/FilelistList' === $currentUri || '/module/file/FilelistList' === $route;
    }

    private function canRenderButton(string $key, string $type): bool
    {
        return $this->contentAiItemProvider->canRender($key, $type);
    }

    /**
     * @param File $resource
     */
    private function generateUriFile(ResourceInterface $resource, string $actionName, string $targetControllerClassName): string
    {
        return $this->buildUriToControllerActionFile($resource, $this->mapAction($actionName), $targetControllerClassName);
    }

    /**
     * @param Folder $resource
     */
    private function generateUriFolder(ResourceInterface $resource, string $actionName, string $targetControllerClassName): string
    {
        return $this->buildUriToControllerActionFolder($resource, $this->mapAction($actionName), $targetControllerClassName);
    }

    /**
     * @throws \InvalidArgumentException
     */
    private function generateUrl(?ResourceInterface $resource, string $callbackAction, string $targetControllerClassName): string
    {
        $resourceIdentifier = null === $resource ? 'unknown' : $resource->getIdentifier();

        if ($resource instanceof File) {
            return $this->generateUriFile($resource, $this->mapAction($callbackAction), $targetControllerClassName);
        }

        if ($resource instanceof Folder) {
            return $this->generateUriFolder($resource, $this->mapAction($callbackAction), $targetControllerClassName);
        }

        throw new \InvalidArgumentException(sprintf('Provided resource is not file or folder: %s', $resourceIdentifier));
    }

    private function generateHtmlButton(string $url, string $label): string
    {
        return '<a href="'.$url.'" class="btn btn-default" title="'.(LocalizationUtility::translate($label) ?? '').'"><span class="t3js-icon icon icon-size-small icon-state-default">
	<span class="icon-markup">
	<svg class="icon-color"><use xlink:href="/typo3/sysext/core/Resources/Public/Icons/T3Icons/sprites/actions.svg#actions-rocket" /></svg>
</span></span></a>';
    }

    private function determineTargetControllerClassName(string $callbackAction): string
    {
        return 'alt' === $callbackAction || 'altTexts' === $callbackAction ? 'AiText' : 'AiImage';
    }
}
