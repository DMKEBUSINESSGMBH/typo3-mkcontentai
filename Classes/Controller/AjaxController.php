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

use DMK\MkContentAi\Domain\Model\Image;
use DMK\MkContentAi\Service\AiAltTextService;
use DMK\MkContentAi\Service\FileService;
use DMK\MkContentAi\Service\SiteLanguageService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

class AjaxController extends BaseController
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
        /** @var array<mixed> $parsedBody */
        $parsedBody = $request->getParsedBody();
        $imageUrl = $parsedBody['imageUrl'] ?? null;

        if (!$imageUrl) {
            $translatedMessage = LocalizationUtility::translate('labelErrorMissingImageUrl', 'mkcontentai') ?? '';

            throw new \Exception($translatedMessage);
        }

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

    public function altTextSaveAction(ServerRequestInterface $request): ResponseInterface
    {
        /** @var string[] $requestBody */
        $requestBody = $request->getParsedBody();

        /** @var Response $response */
        $response = GeneralUtility::makeInstance(Response::class);

        $fileUid = (string) isset($requestBody['fileUid']) ? $requestBody['fileUid'] : null;

        if (empty($fileUid)) {
            $translatedMessage = LocalizationUtility::translate('labelFileParameterError', 'mkcontentai') ?? '';
            $response->getBody()->write($translatedMessage);

            return $response
                ->withHeader('Content-Type', 'text/plain')->withStatus(403);
        }

        try {
            $file = $this->aiAltTextService->getFileById($fileUid);
            if (null == $file) {
                $translatedMessage = LocalizationUtility::translate('labelFileParameterError', 'mkcontentai') ?? '';
                $response->getBody()->write($translatedMessage);

                return $response
                    ->withHeader('Content-Type', 'text/plain')->withStatus(404);
            }
            $altText = $this->aiAltTextService->getAltText($file);
            $metadata = $file->getOriginalResource()->getMetaData();
            $metadata->offsetSet('alternative', $altText);
            $metadata->save();
        } catch (\Exception $e) {
            $response = $response->withStatus(500)
                ->withHeader('Content-Type', 'text/plain');
            $response->getBody()->write($e->getMessage());

            return $response;
        }

        return $response->withHeader('Content-Type', 'text/plain')->withStatus(200);
    }

    /**
     * @return ResponseInterface
     *
     * @throws \TYPO3\CMS\Core\Resource\Exception\ExistingTargetFileNameException
     */
    public function promptResultAjaxAction(ServerRequestInterface $request)
    {
        $clientResponse = $this->initializeClient();

        if (isset($clientResponse['error'])) {
            return new JsonResponse(
                [
                    'error' => $clientResponse['error'],
                ],
                500);
        }
        if (!isset($clientResponse['client'])) {
            $translatedMessage = LocalizationUtility::translate('labelErrorClientIsNotDefined', 'mkcontentai') ?? '';

            throw new \Exception($translatedMessage, 1623345720);
        }
        $client = $clientResponse['client'];

        /** @var array<mixed> $parsedBody */
        $parsedBody = $request->getParsedBody();
        $text = $parsedBody['promptText'] ?? null;

        if (empty($text)) {
            $translatedMessage = LocalizationUtility::translate('labelErrorPromptText', 'mkcontentai') ?? '';

            return new JsonResponse(
                [
                    'error' => $translatedMessage,
                ],
                500);
        }

        try {
            $images = $client->image($text);
            /** @var Image[] $images */
            foreach ($images as $key => $image) {
                $images[$key] = $image->toArray();
            }
            $data = [
                'name' => get_class($client),
                'images' => $images,
            ];
        } catch (\Exception $e) {
            return new JsonResponse(
                [
                    'error' => $e->getMessage(),
                ],
                500);
        }

        return new JsonResponse($data, 200);
    }
}
