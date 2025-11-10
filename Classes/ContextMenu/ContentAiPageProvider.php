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

use DMK\MkContentAi\Domain\Model\TtContent;
use DMK\MkContentAi\Domain\Repository\TtContentRepository;
use DMK\MkContentAi\Utility\SettingsUtility;
use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Backend\ContextMenu\ItemProviders\AbstractProvider;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ContentAiPageProvider extends AbstractProvider
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
        'translateContentEasy' => [
            'type' => 'item',
            'label' => 'LLL:EXT:mkcontentai/Resources/Private/Language/locallang_contentai.xlf:labelTextTranslateContentEasy',
            'iconIdentifier' => 'actions-edit-copy',
            'callbackAction' => 'translateContentEasy',
        ],
        'translateContentPlain' => [
            'type' => 'item',
            'label' => 'LLL:EXT:mkcontentai/Resources/Private/Language/locallang_contentai.xlf:labelTextTranslateContentPlain',
            'iconIdentifier' => 'actions-edit-copy',
            'callbackAction' => 'translateContentPlain',
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
        return 'tt_content' === $this->table;
    }

    public function getPriority(): int
    {
        return 55;
    }

    /**
     * @param array<string, array{
     *     type: string,
     *     label: string,
     *     iconIdentifier: string,
     *     additionalAttributes: array<string,string>,
     *     callbackAction: string
     * }> $items
     *
     * @return array<string, array{
     * type: string,
     * label: string,
     * iconIdentifier: string,
     * additionalAttributes: array<string,string>,
     * callbackAction: string
     * }>
     */
    public function addItems(array $items): array
    {
        $this->initDisabledItems();
        $localItems = $this->prepareItems($this->itemsConfiguration);

        return $items + $localItems;
    }

    /**
     * This method is called for each item this provider adds and checks if given item can be added.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function canRender(string $itemName, string $type): bool
    {
        $canRender = false;
        $availableActions = ['translateContentEasy', 'translateContentPlain'];

        if (!GeneralUtility::makeInstance(SettingsUtility::class)->isApiKeySetForSummAi()) {
            return false;
        }

        if (in_array($itemName, $availableActions)) {
            $canRender = $this->isPageContent() && $this->isValidTypeOfRecord((int) $this->identifier);
        }

        return $canRender;
    }

    public function isPageContent(): bool
    {
        return 'tt_content' === $this->table;
    }

    public function generateUrl(string $itemName): UriInterface
    {
        $parameters = $this->getParameters();
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
     * @return array<string, mixed>
     */
    private function getParameters(): array
    {
        return ['uid' => $this->identifier];
    }

    private function getPathInfo(string $itemName): string
    {
        $majorVersion = GeneralUtility::makeInstance(Typo3Version::class)->getMajorVersion();

        $pathInfoMapping = [
            'translateContentEasy' => [
                $majorVersion => '/module/mkcontentai/AiTranslation/translateContentEasy',
            ],
            'translateContentPlain' => [
                $majorVersion => '/module/mkcontentai/AiTranslation/translateContentPlain',
            ],
        ];

        return $pathInfoMapping[$itemName][$majorVersion] ?? '';
    }

    private function isValidTypeOfRecord(int $uid): bool
    {
        $ttContentRepository = GeneralUtility::makeInstance(TtContentRepository::class);

        /** @var TtContent|null $record */
        $record = $ttContentRepository->findByUid($uid);
        (null === $record) ? $recordType = '' : $recordType = $record->getCtype();

        $availableAction = ['text', 'textpic', 'textmedia'];

        return in_array($recordType, $availableAction);
    }
}
