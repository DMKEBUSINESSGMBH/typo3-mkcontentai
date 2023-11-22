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

use Psr\Http\Message\UriInterface;
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
        'extend' => [
            'type' => 'item',
            'label' => 'Extend',
            'iconIdentifier' => 'actions-rocket',
            'callbackAction' => 'extend',
        ],
        'alt' => [
            'type' => 'item',
            'label' => 'Alt text generate',
            'iconIdentifier' => 'actions-rocket',
            'callbackAction' => 'alt',
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

        $extendUrl = $this->generateUrl($itemName);

        switch ($typo3Version->getMajorVersion()) {
            case 12:
                return [
                    'data-callback-module' => '@t3docs/mkcontentai/context-menu-actions',
                    'data-navigate-uri' => $extendUrl->__toString(),
                ];
            case 11:
                return [
                    'data-callback-module' => 'TYPO3/CMS/Mkcontentai/ContextMenu',
                    'data-navigate-uri' => $extendUrl->__toString(),
                ];
            default:
                throw new \RuntimeException('TYPO3 version not supported');
        }
    }

    private function generateUrl(string $itemName): UriInterface
    {
        $typo3Version = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Information\Typo3Version::class);
        $majorVersion = $typo3Version->getMajorVersion();

        $parameters = $this->getParametersBasedOnVersion($majorVersion);
        $pathInfo = $this->getPathInfo($itemName, $majorVersion);

        $this->updateParametersForItemName($parameters, $itemName, $majorVersion);

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
     * @param array<string, mixed> &$parameters
     */
    private function updateParametersForItemName(array &$parameters, string $itemName, int $version): void
    {
        $actionMapping = [
            'upscale' => 'upscale',
            'extend' => 'cropAndExtend',
            'alt' => 'altText',
        ];

        if (11 === $version) {
            $parameters['tx_mkcontentai_system_mkcontentaicontentai']['action'] = $actionMapping[$itemName] ?? '';
        }

        if ('alt' === $itemName && 11 === $version) {
            $parameters['tx_mkcontentai_system_mkcontentaicontentai']['controller'] = 'AiText';
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function getParametersBasedOnVersion(int $version): array
    {
        if (12 === $version) {
            return ['file' => $this->identifier];
        }

        return [
            'tx_mkcontentai_system_mkcontentaicontentai' => [
                'controller' => 'AiImage',
                'file' => $this->identifier,
            ],
        ];
    }

    private function getPathInfo(string $itemName, int $version): string
    {
        $pathInfoMapping = [
            'upscale' => [
                12 => '/module/mkcontentai/AiImage/upscale',
                11 => '/module/system/MkcontentaiContentai',
            ],
            'extend' => [
                12 => '/module/mkcontentai/AiImage/cropAndExtend',
                11 => '/module/system/MkcontentaiContentai',
            ],
            'alt' => [
                12 => '/module/mkcontentai/AiText/altText',
                11 => '/module/system/MkcontentaiContentai',
            ],
        ];

        return $pathInfoMapping[$itemName][$version] ?? '';
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
            case 'extend':
            case 'alt':
                $canRender = $this->isImage();
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
}
