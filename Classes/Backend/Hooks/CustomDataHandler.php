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

use DMK\MkContentAi\Service\AiAltTextLogsService;
use DMK\MkContentAi\Service\AiTranslationContentService;
use GeorgRinger\News\Domain\Repository\NewsRepository;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class CustomDataHandler
{
    private AiAltTextLogsService $altTextLogsService;
    private AiTranslationContentService $translationService;
    private ConnectionPool $connectionPool;

    public function __construct(AiAltTextLogsService $altTextLogsService, AiTranslationContentService $translationService, ConnectionPool $connectionPool)
    {
        $this->altTextLogsService = $altTextLogsService;
        $this->translationService = $translationService;
        $this->connectionPool = $connectionPool;
    }

    /**
     * This method is called after the DataHandler has processed the field array.
     *
     * @param string                    $status      The status, such as 'new', 'update', 'delete'
     * @param string                    $table       The database table being processed
     * @param int                       $recordId    The record ID being processed
     * @param array<string, string|int> $fieldArray  The field array being processed
     * @param DataHandler               $dataHandler Reference to the DataHandler object
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     *
     * @return void
     */
    public function processDatamap_postProcessFieldArray($status, $table, $recordId, $fieldArray, DataHandler $dataHandler)
    {
        if ('update' === $status && 'sys_file_metadata' === $table && isset($fieldArray['alternative'])) {
            $fileMetadataUid = $recordId;
            /** @var string $alternativeNewMetadata */
            $alternativeNewMetadata = $fieldArray['alternative'];

            $this->altTextLogsService->deleteLogsByFileMetadata($fileMetadataUid, $alternativeNewMetadata);
        }
    }

    /**
     * Detach news translation from original news if translation is deleted.
     *
     * This method is called right before the news translation is deleted using DataHandler.
     * It queries the original news and, if found, detaches the translation from it.
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    public function processCmdmap_preProcess(string $command, string $table, int $identifier): void
    {
        // We only support deletions of news translations at the moment
        if ('delete' !== $command || 'tx_news_domain_model_news' !== $table) {
            return;
        }

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_news_domain_model_news');
        $queryBuilder->getRestrictions()->removeAll();
        $record = $queryBuilder->select('tx_mkcontentai_original_news', 'tx_mkcontentai_translated_news')
            ->from('tx_news_domain_model_news')
            ->where(
                $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->gt('tx_mkcontentai_original_news', 0),
                    $queryBuilder->expr()->gt('tx_mkcontentai_translated_news', 0)
                ),
                $queryBuilder->expr()->eq('uid', $identifier)
            )
            ->executeQuery()
            ->fetchNumeric();

        if (false === $record) {
            return;
        }

        [$original, $translated] = $record;

        // Translation is deleted:
        //   - Unset translation reference from original linked news
        //   - Unset translation reference from original news
        if ($original > 0) {
            $this->detachTranslationFromOriginalLinkedNews($original);
            $this->detachTranslationFromOriginalNews($original);
        }

        // Original is deleted:
        //   - Unset translation reference from original linked news
        //   - Delete translation
        if ($translated > 0) {
            $this->detachTranslationFromOriginalLinkedNews($identifier);
            $this->deleteNewsTranslation($translated);
        }
    }

    private function detachTranslationFromOriginalLinkedNews(int $identifier): void
    {
        $newsRepository = GeneralUtility::makeInstance(NewsRepository::class);
        $news = $newsRepository->findByUid($identifier);

        if (null === $news) {
            return;
        }

        $linkedNewsUid = $this->translationService->getNewsInternalLinkUid($news);

        if (null !== $linkedNewsUid) {
            $this->detachTranslationFromOriginalNews($linkedNewsUid);
        }
    }

    private function detachTranslationFromOriginalNews(int $identifier): void
    {
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([
            'tx_news_domain_model_news' => [
                $identifier => [
                    'tx_mkcontentai_translated_news' => 0,
                ],
            ],
        ], []);
        $dataHandler->process_datamap();
    }

    private function deleteNewsTranslation(int $identifier): void
    {
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], [
            'tx_news_domain_model_news' => [
                $identifier => [
                    'delete' => 1,
                ],
            ],
        ]);
        $dataHandler->process_cmdmap();
    }
}
