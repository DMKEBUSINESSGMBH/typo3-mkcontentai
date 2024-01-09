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

use DMK\MkContentAi\Http\Client\AltTextClient;
use DMK\MkContentAi\Http\Client\ClientInterface;
use DMK\MkContentAi\Http\Client\OpenAiClient;
use DMK\MkContentAi\Http\Client\StabilityAiClient;
use DMK\MkContentAi\Http\Client\StableDiffusionClient;
use DMK\MkContentAi\Service\SiteLanguageService;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class SettingsController extends BaseController
{
    /**
     * Configure settings for various AI engines.
     *
     * @param string               $openAiApiKeyValue     API key for OpenAI client
     * @param array<string, mixed> $stableDiffusionValues Array with specific keys and values
     * @param string               $stabilityAiApiValue   API key for Stability AI client
     * @param string               $altTextAiApiValue     API key for Alt Text AI client
     * @param int                  $imageAiEngine         Indicator of which AI engine to use for image processing
     */
    public function settingsAction(string $openAiApiKeyValue = '', array $stableDiffusionValues = [], string $stabilityAiApiValue = '', string $altTextAiApiValue = '', int $imageAiEngine = 0): ResponseInterface
    {
        $openAi = GeneralUtility::makeInstance(OpenAiClient::class);
        $this->setApiKey($openAiApiKeyValue, $openAi);

        $stableDiffusion = GeneralUtility::makeInstance(StableDiffusionClient::class);
        $this->setApiKey($stableDiffusionValues['api'] ?? '', $stableDiffusion);

        $stabilityAi = GeneralUtility::makeInstance(StabilityAiClient::class);
        $this->setApiKey($stabilityAiApiValue, $stabilityAi);

        $altTextAi = GeneralUtility::makeInstance(AltTextClient::class);
        $this->setApiKey($altTextAiApiValue, $altTextAi);

        /** @var SiteLanguageService $siteLanguageService */
        $siteLanguageService = GeneralUtility::makeInstance(SiteLanguageService::class);

        if ($imageAiEngine) {
            $registry = GeneralUtility::makeInstance(Registry::class);
            $registry->set(AiImageController::class, AiImageController::GENERATOR_ENGINE_KEY, $imageAiEngine);
        }

        if ($this->request->hasArgument('stableDiffusionValues')) {
            $stableDiffusionValues = $this->request->getArgument('stableDiffusionValues');
            if (is_array($stableDiffusionValues)) {
                $stableDiffusionModel = $stableDiffusionValues['model'];
                $stableDiffusion->setCurrentModel($stableDiffusionModel);
            }
        }

        if ($this->request->hasArgument('altTextAiLanguage')) {
            /** @var string $altTextAiLanguage */
            $altTextAiLanguage = $this->request->getArgument('altTextAiLanguage');
            if (isset($altTextAiLanguage)) {
                $this->setLanguage($altTextAiLanguage, $altTextAi, $siteLanguageService);
            }
        }

        $this->view->assignMultiple(
            [
                'openAiApiKey' => $openAi->getMaskedApiKey(),
                'stableDiffusionApiKey' => $stableDiffusion->getMaskedApiKey(),
                'stableDiffusionModel' => $stableDiffusion->getCurrentModel(),
                'stabilityAiApiValue' => $stabilityAi->getMaskedApiKey(),
                'altTextAiApiValue' => $altTextAi->getMaskedApiKey(),
                'imageAiEngine' => SettingsController::getImageAiEngine(),
                'altTextAiLanguage' => $siteLanguageService->getAllAvailableLanguages(),
                'selectedAltTextAiLanguage' => $siteLanguageService->getLanguage(),
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
        if (null === $this->moduleTemplateFactory) {
            throw new \Exception('ModuleTemplateFactory not injected', 1623345720);
        }

        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);
        $moduleTemplate->setContent($this->view->render());

        return $this->htmlResponse($moduleTemplate->renderContent());
    }

    private function setLanguage(string $language, ClientInterface $client, SiteLanguageService $siteLanguageService): void
    {
        if ($language) {
            $siteLanguageService->setLanguage($language);
            $this->addFlashMessage('Language was saved.');
            try {
                $client->validateApiCall();
            } catch (\Exception $e) {
                $this->addFlashMessage($e->getMessage(), '', AbstractMessage::ERROR);
            }
        }
    }

    private function setApiKey(string $key, ClientInterface $client): void
    {
        if ($key) {
            $client->setApiKey($key);
            $this->addFlashMessage('API key was saved.');
            try {
                $client->validateApiCall();
            } catch (\Exception $e) {
                $this->addFlashMessage($e->getMessage(), '', AbstractMessage::ERROR);
            }
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
