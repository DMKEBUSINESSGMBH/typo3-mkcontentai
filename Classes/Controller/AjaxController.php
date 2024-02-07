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
use DMK\MkContentAi\Service\FileService;
use DMK\MkContentAi\Service\SiteLanguageService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

class AjaxController
{
    private FileService $fileService;

    private AiAltTextService $aiAltTextService;

    private SiteLanguageService $siteLanguageService;

    public function __construct()
    {
        $this->fileService = GeneralUtility::makeInstance(FileService::class);
        $this->aiAltTextService = GeneralUtility::makeInstance(AiAltTextService::class);
        $this->siteLanguageService = GeneralUtility::makeInstance(SiteLanguageService::class);
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function blobImage(ServerRequestInterface $request): ResponseInterface
    {
        if (!isset($request->getParsedBody()['imageUrl'])) {
            $translatedMessage = LocalizationUtility::translate('labelErrorMissingImageUrl', 'mkcontentai') ?? '';

            throw new \Exception($translatedMessage);
        }
        $imageUrl = $request->getParsedBody()['imageUrl'];

        $imageData = GeneralUtility::getUrl($imageUrl);
        if (!is_string($imageData)) {
            $translatedMessage = LocalizationUtility::translate('labelErrorDownloadImage', 'mkcontentai') ?? '';

            throw new \Exception($translatedMessage);
        }
        $imageBlob = base64_encode($imageData);

        $response = new Response();
        $response->getBody()->write($imageBlob);

        return $response->withHeader('Content-Type', 'text/plain');
    }

    public function getAltText(ServerRequestInterface $request): ResponseInterface
    {
        /** @var string[] $requestBody */
        $requestBody = $request->getParsedBody();

        /** @var Response $response */
        $response = GeneralUtility::makeInstance(Response::class);

        $fileUid = (string) isset($requestBody['fileUid']) ? $requestBody['fileUid'] : null;
        $pageLanguageUid = isset($requestBody['systemLanguageUid']) ? (int) $requestBody['systemLanguageUid'] : null;

        if (empty($fileUid)) {
            return $response->withHeader('Content-Type', 'text/plain');
        }

        $file = $this->fileService->getFileById($fileUid);

        if (null === $file) {
            return $response->withStatus(404, '')->withHeader('Content-Type', 'text/plain');
        }

        try {
            $languageIsoCode = $this->siteLanguageService->getLanguageIsoCodeByUid($pageLanguageUid);
            $altText = $this->aiAltTextService->getAltText($file, $languageIsoCode);
            $response->getBody()->write($altText);
        } catch (\Exception $e) {
            $response = $response->withStatus(500)
                ->withHeader('Content-Type', 'text/plain');
            $response->getBody()->write($e->getMessage());

            return $response;
        }

        return $response->withHeader('Content-Type', 'text/plain');
    }
}
