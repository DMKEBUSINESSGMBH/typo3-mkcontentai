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
        'upscale' => [
            'type' => 'item',
            'label' => 'Upscale',
            'iconIdentifier' => 'actions-rocket',
            'callbackAction' => 'upscale',
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

    /**
     * @return array<string>
     *
     * @throws \TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException
     */
    protected function getAdditionalAttributes(string $itemName): array
    {
        $typo3Version = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Information\Typo3Version::class);
        if ('upscale' === $itemName) {
            switch ($typo3Version->getMajorVersion()) {
                case 12:
                    /**
                     * @var UriBuilder $uriBuilder
                     */
                    $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
                    $upscaleUrl = $uriBuilder->buildUriFromRoutePath(
                        '/module/mkcontentai/AiImage/upscale',
                        [
                            'file' => $this->identifier,
                        ]
                    );

                    return [
                        'data-callback-module' => '@t3docs/mkcontentai/context-menu-actions',
                        'data-navigate-uri' => $upscaleUrl->__toString(),
                    ];
                case 11:
                    /**
                     * @var UriBuilder $uriBuilder
                     */
                    $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
                    $upscaleUrl = $uriBuilder->buildUriFromRoutePath(
                        '/module/system/MkcontentaiContentai',
                        [
                            'tx_mkcontentai_system_mkcontentaicontentai' => [
                                'action' => 'upscale',
                                'controller' => 'AiImage',
                                'file' => $this->identifier,
                            ],
                        ]
                    );

                    return [
                        'data-callback-module' => 'TYPO3/CMS/Mkcontentai/ContextMenu',
                        'data-navigate-uri' => $upscaleUrl->__toString(),
                    ];
                default:
                    throw new \RuntimeException('TYPO3 version not supported');
            }
        }

        return [];
    }

    /**
     * This method is called for each item this provider adds and checks if given item can be added.
     */
    protected function canRender(string $itemName, string $type): bool
    {
        if ('item' !== $type) {
            return false;
        }
        $canRender = false;
        switch ($itemName) {
            case 'upscale':
                $canRender = $this->canUpscale();
                break;
        }

        return $canRender;
    }

    /**
     * Helper method implementing e.g. access check for certain item.
     */
    protected function canUpscale(): bool
    {
        return 'sys_file' === $this->table && preg_match('/\.(png|jpg)$/', $this->identifier);
    }
}
