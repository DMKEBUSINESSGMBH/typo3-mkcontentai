<?php

declare(strict_types=1);

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
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Recordlist\Event\ModifyRecordListRecordActionsEvent;

/**
 * Add translate buttons to record list actions if EXT:news is installed.
 */
final class NewsRecordEventListener
{
    private IconFactory $iconFactory;
    private PermissionsUtility $permissionsUtility;
    private Typo3Version $typo3Version;
    private UriBuilder $uriBuilder;

    public function __construct(
        IconFactory $iconFactory,
        PermissionsUtility $permissionsUtility,
        Typo3Version $typo3Version,
        UriBuilder $uriBuilder
    ) {
        $this->iconFactory = $iconFactory;
        $this->permissionsUtility = $permissionsUtility;
        $this->typo3Version = $typo3Version;
        $this->uriBuilder = $uriBuilder;
    }

    public function __invoke(ModifyRecordListRecordActionsEvent $event): void
    {
        $table = $event->getTable();
        $record = $event->getRecord();

        // Early return on unsupported table or if news was already processed for translation
        if ('tx_news_domain_model_news' !== $table
            || $this->hasActiveTranslationReference((int) ($record['tx_mkcontentai_original_news'] ?? 0))
            || $this->hasActiveTranslationReference((int) ($record['tx_mkcontentai_translated_news'] ?? 0))
        ) {
            return;
        }

        if ($this->permissionsUtility->userHasAccessToNewsTranslationEasyLanguage()) {
            $this->addAction('translateContentEasy', $event);
        }

        if ($this->permissionsUtility->userHasAccessToNewsTranslationPlainLanguage()) {
            $this->addAction('translateContentPlain', $event);
        }
    }

    private function addAction(string $actionName, ModifyRecordListRecordActionsEvent $event): void
    {
        if (!$event->hasAction($actionName)) {
            $uid = (int) $event->getRecord()['uid'];
            $markup = $this->createActionMarkup($actionName, $uid);

            $event->setAction($markup, $actionName, 'secondary');
        }
    }

    private function createActionMarkup(string $action, int $uid): string
    {
        $classNames = 'dropdown-item dropdown-item-spaced';
        $routePath = '/module/mkcontentai/AiTranslation/'.$action;
        $parameters = [
            'table' => 'tx_news_domain_model_news',
            'uid' => $uid,
        ];

        if (11 === $this->typo3Version->getMajorVersion()) {
            $classNames = 'btn btn-default';
            $routePath = '/module/system/MkcontentaiContentai';
            $parameters = [
                'tx_mkcontentai_system_mkcontentaicontentai' => [
                    'controller' => 'AiTranslation',
                    'action' => $action,
                    'table' => 'tx_news_domain_model_news',
                    'uid' => $uid,
                ],
            ];
        }

        return sprintf(
            '<a href="%s" class="%s" title="%s">%s</a>',
            $this->uriBuilder->buildUriFromRoutePath($routePath, $parameters),
            $classNames,
            LocalizationUtility::translate(
                sprintf('LLL:EXT:mkcontentai/Resources/Private/Language/locallang_contentai.xlf:labelText%s', ucfirst($action))
            ),
            $this->iconFactory->getIcon('actions-translate', Icon::SIZE_SMALL)->render()
        );
    }

    private function hasActiveTranslationReference(int $identifier): bool
    {
        return null !== BackendUtility::getRecord('tx_news_domain_model_news', $identifier);
    }
}
