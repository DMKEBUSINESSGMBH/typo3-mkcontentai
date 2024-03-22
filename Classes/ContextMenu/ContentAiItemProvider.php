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

namespace DMK\MkContentAi\ContextMenu;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Backend\ContextMenu\ItemProviders\AbstractProvider;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ContentAiItemProvider extends AbstractProvider
{
    /**
     * @var array<string, array{
     *     type: string,
     *     label: string,
     *     iconIdentifier: string,
     *     callbackAction: string
     * }>
     */
    protected $itemsConfiguration = [
        'fileUpscale' => [
            'type' => 'item',
            'label' => 'LLL:EXT:mkcontentai/Resources/Private/Language/locallang_contentai.xlf:labelContextMenuUpscale',
            'iconIdentifier' => 'actions-rocket',
            'callbackAction' => 'upscale',
        ],
        'fileExtend' => [
            'type' => 'item',
            'label' => 'LLL:EXT:mkcontentai/Resources/Private/Language/locallang_contentai.xlf:labelContextMenuExtend',
            'iconIdentifier' => 'actions-rocket',
            'callbackAction' => 'extend',
        ],
        'fileAlt' => [
            'type' => 'item',
            'label' => 'LLL:EXT:mkcontentai/Resources/Private/Language/locallang_contentai.xlf:labelContextMenuAlttext',
            'iconIdentifier' => 'actions-rocket',
            'callbackAction' => 'alt',
        ],
        'folderAltTexts' => [
            'type' => 'item',
            'label' => 'LLL:EXT:mkcontentai/Resources/Private/Language/locallang_contentai.xlf:labelContextMenuAlttext',
            'iconIdentifier' => 'actions-rocket',
            'callbackAction' => 'altTexts',
        ],
    ];

    public function canHandle(): bool
    {
        return 'sys_file' === $this->table;
    }

    public function getPriority(): int
    {
        return 55;
    }

    public function setContext(string $table, string $identifier, string $context = ''): void
    {
        $this->table = $table;
        $this->identifier = $identifier;
        $this->context = $context;
    }

    /**
     * @return array<string, array{
     *     type: string,
     *     label: string,
     *     iconIdentifier: string,
     *     callbackAction: string
     * }>
     */
    public function getItemsConfiguration(): array
    {
        return $this->itemsConfiguration;
    }

    /**
     * @return array<string>
     *
     * @throws \TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException
     */
    protected function getAdditionalAttributes(string $itemName): array
    {
        $extendUrl = $this->generateUrl($itemName);

        return [
            'data-callback-module' => 'TYPO3/CMS/Mkcontentai/ContextMenu',
            'data-navigate-uri' => $extendUrl->__toString(),
        ];
    }

    private function generateUrl(string $itemName): Uri
    {
        $pathInfo = '/module/system/MkcontentaiContentai';
        $parameters = [
            'tx_mkcontentai_system_mkcontentaicontentai' => [
                'controller' => 'AiImage',
                'file' => $this->identifier,
            ],
        ];

        if ('fileUpscale' === $itemName) {
            $parameters['tx_mkcontentai_system_mkcontentaicontentai']['action'] = 'upscale';
        }
        if ('fileExtend' === $itemName) {
            $parameters['tx_mkcontentai_system_mkcontentaicontentai']['action'] = 'cropAndExtend';
        }
        if ('fileAlt' === $itemName) {
            $parameters['tx_mkcontentai_system_mkcontentaicontentai']['controller'] = 'AiText';
            $parameters['tx_mkcontentai_system_mkcontentaicontentai']['action'] = 'altText';
        }
        if ('folderAltTexts' === $itemName) {
            $parameters['tx_mkcontentai_system_mkcontentaicontentai']['controller'] = 'AiText';
            $parameters['tx_mkcontentai_system_mkcontentaicontentai']['action'] = 'altTexts';
            $parameters['tx_mkcontentai_system_mkcontentaicontentai']['folderName'] = $this->identifier;
        }

        /**
         * @var UriBuilder $uriBuilder
         */
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $extendUrl = $uriBuilder->buildUriFromRoutePath(
            $pathInfo,
            $parameters
        );

        return $extendUrl;
    }

    /**
     * This method is called for each item this provider adds and checks if given item can be added.
     */
    public function canRender(string $itemName, string $type): bool
    {
        if ('item' !== $type) {
            return false;
        }
        $canRender = false;
        switch ($itemName) {
            case 'fileUpscale':
            case 'fileExtend':
            case 'fileAlt':
                $canRender = $this->isImage();
                break;
            case 'folderAltTexts':
                $canRender = $this->isFolder();
                break;
        }

        return $canRender;
    }

    /**
     * Helper method implementing e.g. access check for certain item.
     */
    protected function isImage(): bool
    {
        return 'sys_file' === $this->table && preg_match('/\.(png|jpg)$/', $this->identifier);
    }

    /**
     * Helper method checking if resource is a folder and exist in the storage.
     */
    protected function isFolder(): bool
    {
        $resourceStorage = GeneralUtility::makeInstance(ResourceFactory::class);
        $object = $resourceStorage->retrieveFileOrFolderObject($this->identifier);

        return $object instanceof Folder;
    }
}
