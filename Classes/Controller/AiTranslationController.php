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

namespace DMK\MkContentAi\Controller;

use DMK\MkContentAi\Backend\Hooks\NewsContentHandler;
use DMK\MkContentAi\Backend\Hooks\PageContentHandler;
use DMK\MkContentAi\Domain\Model\TtContent;
use DMK\MkContentAi\Service\AiTranslationContentService;
use GeorgRinger\News\Domain\Model\News;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Http\ForwardResponse;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AiTranslationController extends BaseController
{
    private AiTranslationContentService $aiTranslationService;
    private PageContentHandler $pageContentHandler;
    private NewsContentHandler $newsContentHandler;
    private LinkService $linkService;

    public function __construct(AiTranslationContentService $aiTranslationService, PageContentHandler $pageContentHandler, NewsContentHandler $newsContentHandler, LinkService $linkService, PageRenderer $pageRenderer)
    {
        $this->aiTranslationService = $aiTranslationService;
        $this->pageContentHandler = $pageContentHandler;
        $this->newsContentHandler = $newsContentHandler;
        $this->linkService = $linkService;

        $pageRenderer->addCssFile('EXT:mkcontentai/Resources/Public/Css/base.css');
    }

    public function translateContentEasyAction(int $uid = 0, string $table = 'tt_content', string $inputTextType = 'html', string $targetLanguageType = 'easy', string $separator = 'hyphen'): ResponseInterface
    {
        return $this->translateContent($uid, $table, $inputTextType, $targetLanguageType, $separator);
    }

    public function translateContentPlainAction(int $uid = 0, string $table = 'tt_content', string $inputTextType = 'html', string $targetLanguageType = 'plain', string $separator = 'hyphen'): ResponseInterface
    {
        return $this->translateContent($uid, $table, $inputTextType, $targetLanguageType, $separator);
    }

    /**
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    private function translateContent(int $uid, string $table, string $inputTextType, string $targetLanguageType, string $separator): ResponseInterface
    {
        try {
            if ('tx_news_domain_model_news' === $table) {
                $record = $this->aiTranslationService->getNewsRecordToTranslate($uid);

                if (null === $record) {
                    return $this->processError('labelErrorRecordAlreadyTranslated');
                }

                $this->translateNewsContent($uid, $record, $inputTextType, $targetLanguageType, $separator);
            } else {
                $record = $this->aiTranslationService->getRecordToTranslate($uid);

                if (null === $record) {
                    return $this->processError('labelErrorRecordSelected');
                }

                $this->translatePageContent($record, $inputTextType, $targetLanguageType, $separator);
            }
        } catch (\Exception $e) {
            $this->addFlashMessage($e->getMessage(), '', AbstractMessage::ERROR);

            return $this->handleResponse();
        }

        return $this->buildUrl((int) $record->getPid(), $table);
    }

    private function translateNewsContent(int $uid, News $record, string $inputTextType, string $targetLanguageType, string $separator): int
    {
        $linkedNewsUid = $this->aiTranslationService->getNewsInternalLinkUid($record);

        // Translate linked news first before continuing with original record
        if ($linkedNewsUid > 0) {
            $linkedRecord = $this->aiTranslationService->getNewsRecordToTranslate($linkedNewsUid);

            if (null !== $linkedRecord) {
                $translatedUid = $this->translateNewsContent($linkedNewsUid, $linkedRecord, $inputTextType, $targetLanguageType, $separator);

                // Update reference to translated news in internal url of original news
                $linkParameters = $this->linkService->resolveByStringRepresentation($record->getInternalurl());
                $linkParameters['uid'] = $translatedUid;
                $internalUrlToTranslatedLinkedRecord = $this->linkService->asString($linkParameters);
                $record->setInternalurl($internalUrlToTranslatedLinkedRecord);
            }
        }

        $title = $record->getTitle();
        $teaser = $record->getTeaser();
        $bodyText = $record->getBodytext();
        $additionalContent = $this->aiTranslationService->getNewsContentToTranslate($uid) ?? '';

        if ('' !== $additionalContent) {
            $bodyText .= ' '.$additionalContent;
        }

        if ($title) {
            $translatedTitle = $this->aiTranslationService->getTranslation($title, $this->aiTranslationService->getSummAiUserEmail(), $inputTextType, $targetLanguageType, $separator);
            $title = $translatedTitle->translated_text ?? '';
        }

        if ($teaser) {
            $translatedTeaser = $this->aiTranslationService->getTranslation($teaser, $this->aiTranslationService->getSummAiUserEmail(), $inputTextType, $targetLanguageType, $separator);
            $teaser = $translatedTeaser->translated_text ?? '';
        }

        if ($bodyText) {
            $translatedBodyText = $this->aiTranslationService->getTranslation($bodyText, $this->aiTranslationService->getSummAiUserEmail(), $inputTextType, $targetLanguageType, $separator);
            $bodyText = $translatedBodyText->translated_text ?? '';
        }

        $appendedContentUid = $this->aiTranslationService->getSummAiAppendedContentUid();
        $showDisclaimer = $this->aiTranslationService->getSummAiDisclaimer();

        return $this->newsContentHandler->createNewsRecord($record, $title, $teaser, $bodyText, $targetLanguageType, $appendedContentUid, $showDisclaimer);
    }

    private function translatePageContent(TtContent $record, string $inputTextType, string $targetLanguageType, string $separator): void
    {
        $bodyText = $record->getBodytext();
        $translatedBodyText = $this->aiTranslationService->getTranslation($bodyText, $this->aiTranslationService->getSummAiUserEmail(), $inputTextType, $targetLanguageType, $separator);

        $this->pageContentHandler->copyContentRecord($record->getUid(), $record->getPid(), $translatedBodyText->translated_text, $targetLanguageType);
    }

    private function processError(string $msgKey): ResponseInterface
    {
        $response = new ForwardResponse('filelist');
        $translatedMessage = LocalizationUtility::translate($msgKey, 'mkcontentai') ?? '';
        $this->addFlashMessage($translatedMessage, '', AbstractMessage::ERROR);

        return $response->withControllerName('AiImage');
    }

    private function buildUrl(int $recordPid, string $table): ResponseInterface
    {
        $routeName = 'tx_news_domain_model_news' === $table ? 'web_list' : 'web_layout';
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $recordUrl = $uriBuilder->buildUriFromRoute($routeName, [
            'id' => $recordPid,
        ]);

        $redirectResponse = GeneralUtility::makeInstance(RedirectResponse::class, $recordUrl);

        return $redirectResponse;
    }

    protected function handleResponse(): ResponseInterface
    {
        if (null === $this->moduleTemplateFactory) {
            $translatedMessage = LocalizationUtility::translate('labelErrorModuleTemplateFactory', 'mkcontentai') ?? '';

            throw new \Exception($translatedMessage, 1623345720);
        }

        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);
        $moduleTemplate->setContent($this->view->render());

        return $this->htmlResponse($moduleTemplate->renderContent());
    }
}
