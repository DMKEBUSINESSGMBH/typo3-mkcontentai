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

use DMK\MkContentAi\Http\Client\ClientInterface;
use DMK\MkContentAi\Http\Client\OpenAiClient;
use DMK\MkContentAi\Http\Client\StabilityAiClient;
use DMK\MkContentAi\Http\Client\StableDiffusionClient;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class SettingsController extends BaseController
{
    public function settingsAction(string $openAiApiKeyValue = null, string $stableDiffusionApiValue = null, string $stabilityAiApiValue = null, int $imageAiEngine = 0, string $stableDiffusionModel = 'empty')
    {
        $openAi = GeneralUtility::makeInstance(OpenAiClient::class);
        if ($openAiApiKeyValue) {
            $this->setApiKey($openAiApiKeyValue, $openAi);
        }

        $stableDiffusion = GeneralUtility::makeInstance(StableDiffusionClient::class);
        if ($stableDiffusionApiValue) {
            $this->setApiKey($stableDiffusionApiValue, $stableDiffusion);
        }

        $stabilityAi = GeneralUtility::makeInstance(StabilityAiClient::class);
        if ($stabilityAiApiValue) {
            $this->setApiKey($stabilityAiApiValue, $stabilityAi);
        }

        if ($imageAiEngine) {
            $registry = GeneralUtility::makeInstance(Registry::class);
            $registry->set(AiImageController::class, AiImageController::GENERATOR_ENGINE_KEY, $imageAiEngine);
        }

        if ($this->request->hasArgument('stableDiffusionModel')) {
            $stableDiffusion->setCurrentModel($stableDiffusionModel);
        }

        $this->view->assignMultiple(
            [
                'openAiApiKey' => $openAi->getMaskedApiKey(),
                'stableDiffusionApiKey' => $stableDiffusion->getMaskedApiKey(),
                'stabilityAiApiValue' => $stabilityAi->getMaskedApiKey(),
                'currentStabeDiffusionModel' => $stableDiffusion->getCurrentModel(),
                'imageAiEngine' => SettingsController::getImageAiEngine(),
            ]
        );

        try {
            $this->view->assignMultiple(
                [
                    'stabeDiffusionModels' => array_merge(
                        [
                            'none' => [
                                'model_id' => '',
                            ],
                        ],
                        $stableDiffusion->modelList()
                    ),
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
        $imageEngineKey = intval($registry->get(AiImageController::class, AiImageController::GENERATOR_ENGINE_KEY));
        if (!array_key_exists($imageEngineKey, AiImageController::GENERATOR_ENGINE)) {
            $imageEngineKey = array_key_first(AiImageController::GENERATOR_ENGINE);
        }

        return $imageEngineKey;
    }
}
