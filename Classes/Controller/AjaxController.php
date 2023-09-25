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

namespace DMK\MkContentAi\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class AjaxController
{
    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function blobImage(ServerRequestInterface $request): ResponseInterface
    {
        if (!isset($request->getParsedBody()['imageUrl'])) {
            throw new \Exception('Missing imageUrl');
        }
        $imageUrl = $request->getParsedBody()['imageUrl'];

        $imageData = GeneralUtility::getUrl($imageUrl);
        if (!is_string($imageData)) {
            throw new \Exception('Could not download image');
        }
        $imageBlob = base64_encode($imageData);

        $response = new Response();
        $response->getBody()->write($imageBlob);

        return $response->withHeader('Content-Type', 'text/plain');
    }
}
