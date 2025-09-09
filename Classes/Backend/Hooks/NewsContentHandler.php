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

namespace DMK\MkContentAi\Backend\Hooks;

use GeorgRinger\News\Domain\Model\News;
use GeorgRinger\News\Domain\Model\NewsInternal;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

class NewsContentHandler
{
    /**
     * Create translated news record from original record.
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function createNewsRecord(
        News $record,
        string $title,
        string $teaser,
        string $bodyText,
        string $targetLanguageType,
        ?int $appendedContentUid,
        bool $showDisclaimer
    ): int {
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $fullDisclaimer = sprintf(
            '<em>%s</em><br><em>%s</em>',
            LocalizationUtility::translate('labelAiDisclaimer', 'mkcontentai'),
            LocalizationUtility::translate('labelAiDisclaimer2', 'mkcontentai')
        );
        $fullBodyText = $bodyText.($showDisclaimer ? '<br><br>'.$fullDisclaimer : '');

        $newsRecordData = [
            'pid' => $record->getPid(),
            'type' => $record->getType(),
            'title' => '(Transformed into '.$targetLanguageType.' language) '.strip_tags($title),
            'teaser' => strip_tags($teaser),
            'bodytext' => $fullBodyText,
            'tx_mkcontentai_original_news' => $record->getUid(),
            'datetime' => null !== $record->getDatetime() ? $record->getDatetime()->getTimestamp() : null,
            'crdate' => time(),
            'tstamp' => time(),
        ];

        if ($appendedContentUid > 0) {
            $newsRecordData['content_elements'] = $appendedContentUid;
        }

        if ($record instanceof NewsInternal) {
            $newsRecordData['internalurl'] = $record->getInternalUrl();
        }

        $newIdentifier = StringUtility::getUniqueId('NEW_');
        $dataMap = [
            'tx_news_domain_model_news' => [
                $newIdentifier => $newsRecordData,
            ],
        ];

        $this->executeDataHandler($dataHandler, $dataMap);

        $newUid = $dataHandler->substNEWwithIDs[$newIdentifier];

        $this->updateOriginalRecord($dataHandler, $record, $newUid);

        return $newUid;
    }

    private function updateOriginalRecord(DataHandler $dataHandler, News $record, int $translatedUid): void
    {
        $this->executeDataHandler(
            $dataHandler,
            [
                'tx_news_domain_model_news' => [
                    $record->getUid() => [
                        'tx_mkcontentai_translated_news' => $translatedUid,
                    ],
                ],
            ]
        );
    }

    /**
     * @param array<string, array<string|int, array<string, mixed>>> $data
     */
    private function executeDataHandler(DataHandler $dataHandler, array $data): void
    {
        $dataHandler->start($data, []);
        $dataHandler->process_datamap();
    }
}
