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

use DMK\MkContentAi\Http\Client\ClientInterface;
use DMK\MkContentAi\Http\Client\OpenAiClient;
use DMK\MkContentAi\Http\Client\StableDifussionClient;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class SettingsController extends BaseController
{
    public function settingsAction(string $openAiApiKeyValue = null, string $stableDiffusionApiValue = null, int $imageAiEngine = 0, string $stableDiffusionModel = 'empty'): void
    {
        $openAi = GeneralUtility::makeInstance(OpenAiClient::class);
        if ($openAiApiKeyValue) {
            $this->setApiKey($openAiApiKeyValue, $openAi);
        }

        $stableDiffusion = GeneralUtility::makeInstance(StableDifussionClient::class);
        if ($stableDiffusionApiValue) {
            $this->setApiKey($stableDiffusionApiValue, $stableDiffusion);
        }

        if ($imageAiEngine) {
            $registry = GeneralUtility::makeInstance(Registry::class);
            $registry->set(OpenAiController::class, OpenAiController::GENERATOR_ENGINE_KEY, $imageAiEngine);
        }

        if ($this->request->hasArgument('stableDiffusionModel')) {
            $stableDiffusion->setCurrentModel($stableDiffusionModel);
        }

        try {
            $this->view->assignMultiple(
                [
                    'openAiApiKey' => $openAi->getApiKey(),
                    'stableDiffusionApiKey' => $stableDiffusion->getApiKey(),
                    'stabeDiffusionModels' => array_merge(
                        [
                            'none' => [
                                'model_id' => '',
                            ],
                        ],
                        $stableDiffusion->modelList()
                    ),
                    'currentStabeDiffusionModel' => $stableDiffusion->getCurrentModel(),
                    'imageAiEngine' => SettingsController::getImageAiEngine(),
                ]
            );
        } catch (\Exception $e) {
            $this->addFlashMessage($e->getMessage(), '', AbstractMessage::ERROR);
        }
    }

    private function setApiKey(string $key, ClientInterface $client): void
    {
        $client->setApiKey($key);
        $this->addFlashMessage('API key was saved.');
        try {
            $client->validateApiCall();
        } catch (\Exception $e) {
            $this->addFlashMessage($e->getMessage(), '', AbstractMessage::ERROR);
        }
    }

    public static function getImageAiEngine(): int
    {
        $registry = GeneralUtility::makeInstance(Registry::class);
        $imageEngineKey = intval($registry->get(OpenAiController::class, OpenAiController::GENERATOR_ENGINE_KEY));
        if (!array_key_exists($imageEngineKey, OpenAiController::GENERATOR_ENGINE)) {
            $imageEngineKey = array_key_first(OpenAiController::GENERATOR_ENGINE);
        }

        return $imageEngineKey;
    }
}
