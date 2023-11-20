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

use DMK\MkContentAi\Http\Client\AltTextClient;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Domain\Model\File;

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
    public function altTextAction(File $file): ResponseInterface
    {
        $altText = '';
        $altTextClient = GeneralUtility::makeInstance(AltTextClient::class);
        try {
            $altText = $altTextClient->getByAssetId($file->getOriginalResource()->getUid());
        } catch (\Exception $e) {
            if (404 != $e->getCode()) {
                $this->addFlashMessage($e->getMessage(), '', AbstractMessage::ERROR);
            }
        }

        try {
            if (!$altText) {
                $altText = $altTextClient->getAltTextForFile($file);
            }
        } catch (\Exception $e) {
            $this->addFlashMessage($e->getMessage(), '', AbstractMessage::ERROR);
        }

        $this->view->assignMultiple(
            [
                'file' => $file,
                'altText' => $altText,
            ]
        );

        return $this->handleResponse();
    }

    protected function handleResponse(): ResponseInterface
    {
        if (null === $this->moduleTemplateFactory) {
            throw new \Exception('ModuleTemplateFactory not injected', 1623345720);
        }

        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);
        $moduleTemplate->setContent($this->view->render());

        return $this->htmlResponse($moduleTemplate->renderContent());
    }
}
