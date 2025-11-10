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

use DMK\MkContentAi\Controller\AiImageController;
use DMK\MkContentAi\Controller\SettingsController;
use DMK\MkContentAi\Http\Client\OpenAiClient;
use DMK\MkContentAi\Http\Client\StabilityAiClient;
use DMK\MkContentAi\Utility\SettingsUtility;
use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Backend\ContextMenu\ItemProviders\AbstractProvider;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Information\Typo3Version;
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
        'filePrepareImageToVideo' => [
            'type' => 'item',
            'label' => 'LLL:EXT:mkcontentai/Resources/Private/Language/locallang_contentai.xlf:labelContextMenuImageToVideo',
            'iconIdentifier' => 'actions-rocket',
            'callbackAction' => 'prepareImageToVideo',
        ],
    ];

    public function setContext(string $table, string $identifier, string $context = ''): void
    {
        $this->table = $table;
        $this->identifier = $identifier;
        $this->context = $context;
    }

    public function canHandle(): bool
    {
        return 'sys_file' === $this->table;
    }

    public function getPriority(): int
    {
        return 55;
    }

    public function generateUrl(string $itemName): UriInterface
    {
        $parameters = $this->getParameters($itemName);
        $pathInfo = $this->getPathInfo($itemName);

        /**
         * @var UriBuilder $uriBuilder
         */
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $extendedUrl = $uriBuilder->buildUriFromRoutePath(
            $pathInfo,
            $parameters
        );

        return $extendedUrl;
    }

    /**
     * This method is called for each item this provider adds and checks if given item can be added.
     */
    public function canRender(string $itemName, string $type): bool
    {
        if ('item' !== $type) {
            return false;
        }
        $imageAiEngine = SettingsController::getImageAiEngine();
        $canRender = false;

        if (
            (('fileUpscale' === $itemName || 'fileExtend' === $itemName || 'filePrepareImageToVideo' === $itemName) && true === $this->checkAllowedOperationsByClient($itemName, $imageAiEngine) && GeneralUtility::makeInstance(SettingsUtility::class)->isApiKeySetForImageGeneration())
            || 'fileAlt' === $itemName && GeneralUtility::makeInstance(SettingsUtility::class)->isApiKeySetForAltTextAi()
        ) {
            return $this->isImage();
        }

        if ('folderAltTexts' === $itemName &&  GeneralUtility::makeInstance(SettingsUtility::class)->isApiKeySetForAltTextAi()) {
            return $this->isFolder();
        }

        return $canRender;
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
            'data-callback-module' => '@t3docs/mkcontentai/context-menu-actions',
            'data-navigate-uri' => $extendUrl->__toString(),
        ];
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

    /**
     * @return array<string, mixed>
     */
    private function getParameters(string $itemName): array
    {
        if ('folderAltTexts' === $itemName) {
            return ['folderName' => $this->identifier];
        }

        return ['file' => $this->identifier];
    }

    private function getPathInfo(string $itemName): string
    {
        $majorVersion = GeneralUtility::makeInstance(Typo3Version::class)->getMajorVersion();

        $pathInfoMapping = [
            'fileUpscale' => [
                $majorVersion => '/module/mkcontentai/AiImage/upscale',
            ],
            'fileExtend' => [
                $majorVersion => '/module/mkcontentai/AiImage/cropAndExtend',
            ],
            'fileAlt' => [
                $majorVersion => '/module/mkcontentai/AiText/altText',
            ],
            'folderAltTexts' => [
                $majorVersion => '/module/mkcontentai/AiText/altTexts',
            ],
            'filePrepareImageToVideo' => [
                $majorVersion => '/module/mkcontentai/AiVideo/prepareImageToVideo',
            ],
        ];

        return $pathInfoMapping[$itemName][$majorVersion] ?? '';
    }

    /**
     *  Helper method checking if current AI Client has permissions for a given operation.
     */
    private function checkAllowedOperationsByClient(string $itemName, int $imageAiEngine): bool
    {
        $stabilityAiClient = GeneralUtility::makeInstance(StabilityAiClient::class);
        $openAiClient = GeneralUtility::makeInstance(OpenAiClient::class);

        foreach ([$stabilityAiClient, $openAiClient] as $aiClient) {
            if (get_class($aiClient) === AiImageController::GENERATOR_ENGINE[$imageAiEngine] && in_array(lcfirst(str_replace('file', '', $itemName)), $aiClient->getAllowedOperations())) {
                return true;
            }
        }

        return false;
    }
}
