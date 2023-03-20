<?php

/*
 * Copyright notice
 *
 * (c) DMK E-BUSINESS GmbH <dev@dmk-ebusiness.de>
 * All rights reserved
 *
 * This file is part of TYPO3 CMS-based extension "container" by b13.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

namespace DMK\MkContentAi\Controller;

use DMK\MkContentAi\Http\Client\OpenAiClient;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class SettingsController extends BaseController
{
    /**
     * @return void
     */
    public function openAiAction(string $apiKeyValue = null)
    {
        $openAi = GeneralUtility::makeInstance(OpenAiClient::class);
        if (null != $apiKeyValue) {
            $openAi->setApiKey($apiKeyValue);
            $this->addFlashMessage('API key was saved.');
            try {
                $openAi->listModels();
            } catch (\Exception $e) {
                $this->addFlashMessage($e->getMessage(), '', AbstractMessage::ERROR);
            }
        }
        $this->view->assignMultiple(
            [
                'apiKey' => $openAi->getApiKey(),
            ]
        );
    }
}
