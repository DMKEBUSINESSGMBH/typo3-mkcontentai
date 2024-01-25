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

use DMK\MkContentAi\Service\AiAltTextService;
use DMK\MkContentAi\Service\SiteLanguageService;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Domain\Model\File;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * This file is part of the "DMK Content AI" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * (c) 2023
 */

/**
 * ImageController.
 */
class AiTextController extends BaseController
{
    public AiAltTextService $aiAltTextService;
    public SiteLanguageService $siteLanguageService;

    public function __construct(AiAltTextService $aiAltTextService, SiteLanguageService $siteLanguageService)
    {
        $this->aiAltTextService = $aiAltTextService;
        $this->siteLanguageService = $siteLanguageService;
    }

    public function altTextAction(File $file): ResponseInterface
    {
        $this->view->assignMultiple(
            [
                'file' => $file,
                'altText' => $this->getAltTextForFile($file),
                'languageName' => $this->siteLanguageService->getFullLanguageName(),
            ]
        );

        return $this->handleResponse();
    }

    public function altTextSaveAction(File $file): ResponseInterface
    {
        $altText = $this->getAltTextForFile($file);

        $metadata = $file->getOriginalResource()->getMetaData();
        $metadata->offsetSet('alternative', $altText);
        $metadata->save();

        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $metaDataUid = $file->getOriginalResource()->getMetaData()->get()['uid'];
        $editUrl = $uriBuilder->buildUriFromRoute('record_edit', [
            'edit[sys_file_metadata]['.$metaDataUid.']' => 'edit',
        ]);

        $redirectResponse = GeneralUtility::makeInstance(RedirectResponse::class, $editUrl);

        return $redirectResponse;
    }

    private function getAltTextForFile(File $file): string
    {
        $altTextFromFile = '';

        try {
            $altTextFromFile = $this->aiAltTextService->getAltText($file);
        } catch (\Exception $e) {
            $this->addFlashMessage($e->getMessage(), '', AbstractMessage::ERROR);
        }

        return $altTextFromFile;
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
