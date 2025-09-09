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

namespace DMK\MkContentAi\Service;

use DMK\MkContentAi\Domain\Model\TtContent;
use DMK\MkContentAi\Domain\Repository\TtContentRepository;
use DMK\MkContentAi\Http\Client\SummAiClient;
use GeorgRinger\News\Domain\Model\News;
use GeorgRinger\News\Domain\Model\NewsInternal;
use GeorgRinger\News\Domain\Repository\NewsRepository;
use TYPO3\CMS\Core\DataHandling\SoftReference\TypolinkSoftReferenceParser;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

class AiTranslationContentService
{
    private const MAX_INPUT_TEXT_LENGTH = 10000;

    private SummAiClient $summAiClient;
    private TtContentRepository $ttContentRepository;
    private TypolinkSoftReferenceParser $softReferenceParser;
    private ?NewsRepository $newsRepository = null;

    public function __construct(SummAiClient $summAiClient, TtContentRepository $ttContentRepository, TypolinkSoftReferenceParser $softReferenceParser)
    {
        $this->summAiClient = $summAiClient;
        $this->ttContentRepository = $ttContentRepository;
        $this->softReferenceParser = $softReferenceParser;
    }

    public function getTranslation(string $inputText, string $userEmail, string $inputTextType, string $targetLanguageType, string $separator): \stdClass
    {
        if (mb_strlen($inputText) >= self::MAX_INPUT_TEXT_LENGTH) {
            $translatedMessage = LocalizationUtility::translate('labelErrorTextTooLong', 'mkcontentai') ?? '';
            throw new \Exception($translatedMessage, 1623345721);
        }

        return $this->summAiClient->sendContentToTranslate($inputText, $userEmail, $inputTextType, $targetLanguageType, $separator);
    }

    public function getSummAiUserEmail(): string
    {
        return $this->summAiClient->getUserEmail();
    }

    public function getRecordToTranslate(int $recordUid): ?TtContent
    {
        return $this->ttContentRepository->findByUid($recordUid);
    }

    public function getNewsRecordToTranslate(int $recordUid): ?News
    {
        return $this->getNewsRepository()->findByUid($recordUid, false);
    }

    public function getNewsContentToTranslate(int $recordUid): ?string
    {
        $ttContents = $this->ttContentRepository->findRelatedNews($recordUid);
        $cTypesValid = $this->summAiClient->getNewsContentTypes();
        $bodytext = '';

        /** @var TtContent $ttContent */
        foreach ($ttContents as $ttContent) {
            if (in_array($ttContent->getCtype(), $cTypesValid, true)) {
                $bodytext .= $ttContent->getBodytext();
            }
        }

        return $bodytext ?: null;
    }

    public function getSummAiAppendedContentUid(): ?int
    {
        return $this->summAiClient->getSummAiAppendedContentUid();
    }

    public function getSummAiDisclaimer(): bool
    {
        return $this->summAiClient->showSummAiDisclaimer();
    }

    public function getNewsInternalLinkUid(News $record): ?int
    {
        if (!($record instanceof NewsInternal)) {
            return null;
        }

        $uid = (int) $record->getUid();
        $record = $this->getNewsRepository()->findByUid($uid, false);

        if (null === $record) {
            return null;
        }

        $url = $record->getInternalurl();

        if (empty($url)) {
            return null;
        }

        $parserResult = $this->softReferenceParser->parse('tx_news_domain_model_news', 'internalurl', $uid, $url);
        $elements = $parserResult->getMatchedElements();
        $firstElement = reset($elements);

        if (false === $firstElement) {
            return null;
        }

        [$table, $uid] = GeneralUtility::trimExplode(':', $firstElement['subst']['recordRef'] ?? ':', false, 2);

        if (empty($table) || empty($uid) || 'tx_news_domain_model_news' !== $table) {
            return null;
        }

        return (int) $uid;
    }

    /**
     * Create news repository on demand (avoid DI since EXT:news might not be loaded).
     */
    private function getNewsRepository(): NewsRepository
    {
        if (null === $this->newsRepository) {
            $this->newsRepository = GeneralUtility::makeInstance(NewsRepository::class);
        }

        return $this->newsRepository;
    }
}
