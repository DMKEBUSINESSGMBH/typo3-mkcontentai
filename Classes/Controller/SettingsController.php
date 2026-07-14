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

use DMK\MkContentAi\DTO\SettingsDTO;
use DMK\MkContentAi\DTO\SettingsRequestDTO;
use DMK\MkContentAi\Http\Client\ClientInterface;
use DMK\MkContentAi\Http\Client\SummAiClient;
use DMK\MkContentAi\Service\AiAltTextService;
use DMK\MkContentAi\Service\AiImageService;
use DMK\MkContentAi\Service\SiteLanguageService;
use DMK\MkContentAi\Utility\PermissionsUtility;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Fluid\View\TemplateView;

class SettingsController extends BaseController
{
    private PermissionsUtility $permissionsUtility;
    private AiImageService $aiImageService;

    public function injectPermissionsUtility(PermissionsUtility $permissionsUtility): void
    {
        $this->permissionsUtility = $permissionsUtility;
    }

    public function injectAiImageService(AiImageService $aiImageService): void
    {
        $this->aiImageService = $aiImageService;
    }

    public function settingsAction(?SettingsRequestDTO $settingsRequestDTO = null): ResponseInterface
    {
        $settingsRequestDTO = $settingsRequestDTO ?? SettingsRequestDTO::empty();
        $validateSumAiEmail = 'POST' === $this->request->getMethod() && !empty($settingsRequestDTO->getSummAiUserEmail());
        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $pageRenderer->addCssFile('EXT:mkcontentai/Resources/Public/Css/base.css');
        $this->moduleTemplateFactory = GeneralUtility::makeInstance(ModuleTemplateFactory::class);
        /** @var TemplateView $view */
        $view = $this->view;

        if (false === $this->permissionsUtility->userHasAccessToSettings()) {
            $moduleTemplate = $this->moduleTemplateFactory->create($this->request);
            $translatedMessage = LocalizationUtility::translate('labelErrorSettingsPermissions', 'mkcontentai') ?? '';
            $this->addFlashMessage($translatedMessage, '', AbstractMessage::WARNING, false);
            $moduleTemplate->setContent($view->renderSection('InsufficientPermissions'));

            return $this->htmlResponse($moduleTemplate->renderContent());
        }

        $openAi = SettingsDTO::createOpenAiClient($settingsRequestDTO->getOpenAiApiKeyValue());
        $stabilityAi = SettingsDTO::createStabilityAiClient($settingsRequestDTO->getStabilityAiApiValue());
        /** @var string $stableDiffusionApiKey */
        $stableDiffusionApiKey = $settingsRequestDTO->getStableDiffusionAiApiValue();
        $stableDiffusion = SettingsDTO::createStableDiffusionClient($stableDiffusionApiKey);
        $altTextAi = SettingsDTO::createAltTextClient($settingsRequestDTO->getAltTextAiApiValue());
        $openAiAltText = SettingsDTO::createOpenAiAltTextClient(
            $settingsRequestDTO->getOpenAiAltTextApiKeyValue(),
            $settingsRequestDTO->getOpenAiAltTextModel()
        );
        $summAi = SettingsDTO::createSummAiClient($settingsRequestDTO->getSummAiApiValue(), $settingsRequestDTO->getSummAiUserEmail());
        /** @var SummAiClient $summAiClient */
        $summAiClient = $summAi->getClient();

        // Save the alt text provider choice if submitted
        if ('POST' === $this->request->getMethod() && null !== $settingsRequestDTO->getAltTextProvider()) {
            AiAltTextService::setAltTextProvider($settingsRequestDTO->getAltTextProvider());
        }

        try {
            if ('POST' === $this->request->getMethod()) {
                $this->validateApiCalls($openAi, $stabilityAi, $stableDiffusion, $altTextAi, $openAiAltText, $summAi);
            }
            $summAiClient->setEmail($summAiClient->checkEmailFromRequest($settingsRequestDTO->getSummAiUserEmail()), $validateSumAiEmail);
            $modelList = $stableDiffusion->getClient()->modelList();
        } catch (\Exception $e) {
            $this->addFlashMessage($e->getMessage(), '', AbstractMessage::ERROR, false);
            $modelList = [];
        }
        $this->addMessagesAboutSavedApiKeys(
            $settingsRequestDTO->getOpenAiApiKeyValue(),
            $settingsRequestDTO->getStabilityAiApiValue(),
            $stableDiffusionApiKey,
            $settingsRequestDTO->getAltTextAiApiValue(),
            $settingsRequestDTO->getOpenAiAltTextApiKeyValue()
        );

        /** @var SiteLanguageService $siteLanguageService */
        $siteLanguageService = GeneralUtility::makeInstance(SiteLanguageService::class);
        $this->aiImageService->setAiImageEngine($settingsRequestDTO->getImageAiEngine() ?? 0);

        if (!empty($settingsRequestDTO->getSelectedStableDiffusionModel())) {
            $stableDiffusion->getClient()->setCurrentModel($settingsRequestDTO->getSelectedStableDiffusionModel());
        }

        if (!empty($settingsRequestDTO->getSelectedAltTextAiLanguage())) {
            /** @var ClientInterface $altTextClient */
            $altTextClient = $altTextAi->getClient();

            /** @var string $altTextAiLanguage */
            $altTextAiLanguage = $settingsRequestDTO->getSelectedAltTextAiLanguage();
            $siteLanguageService->setLanguageAltTextWithTestApiCall($altTextAiLanguage, $altTextClient);
        }

        $settingsRequestDTO->setSummAiUserEmail($summAiClient->getUserEmail());
        $settingsRequestDTO->setImageAiEngine(SettingsController::getImageAiEngine());
        $settingsRequestDTO->setAltTextAiLanguage($siteLanguageService->getAllAvailableLanguages());
        $settingsRequestDTO->setSelectedAltTextAiLanguage($siteLanguageService->getLanguage());
        $settingsRequestDTO->setStableDiffusionValues(array_merge(
            [
                'none' => ['model_id' => ''],
            ], $modelList));
        $settingsRequestDTO->setAltTextProvider(AiAltTextService::getAltTextProvider());

        // Populate the current model into the DTO for display in the template
        /** @var \DMK\MkContentAi\Http\Client\OpenAiAltTextClient $openAiAltTextClient */
        $openAiAltTextClient = $openAiAltText->getClient();
        $settingsRequestDTO->setOpenAiAltTextModel($openAiAltTextClient->getModel());

        try {
            $this->view->assignMultiple(
                [
                    'openAi'         => $openAi,
                    'stableDiffusion' => $stableDiffusion,
                    'stabilityAi'    => $stabilityAi,
                    'altTextAi'      => $altTextAi,
                    'openAiAltText'  => $openAiAltText,
                    'summAi'         => $summAi,
                    'settingsRequestDTO' => $settingsRequestDTO,
                ]
            );
        } catch (\Exception $e) {
            $this->addFlashMessage($e->getMessage(), '', AbstractMessage::ERROR, false);
        }

        if (null === $this->moduleTemplateFactory) {
            $translatedMessage = LocalizationUtility::translate('labelErrorModuleTemplateFactory', 'mkcontentai') ?? '';
            throw new \Exception($translatedMessage, 1623345720);
        }

        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);
        $moduleTemplate->setContent($this->view->render());

        return $this->htmlResponse($moduleTemplate->renderContent());
    }

    public static function getImageAiEngine(): int
    {
        $registry = self::getRegistry();
        $imageEngineKey = intval($registry->get(AiImageController::class, AiImageController::GENERATOR_ENGINE_KEY));
        if (!array_key_exists($imageEngineKey, AiImageController::GENERATOR_ENGINE)) {
            $imageEngineKey = array_key_first(AiImageController::GENERATOR_ENGINE);
        }

        return $imageEngineKey;
    }

    protected function addMessageAboutSavedApiKey(?string $key): void
    {
        if ('' === $key || null === $key) {
            return;
        }

        $translatedMessage = LocalizationUtility::translate('labelSavedKey', 'mkcontentai') ?? '';
        $this->addFlashMessage($translatedMessage);
    }

    private function addMessagesAboutSavedApiKeys(
        ?string $openAiApiKeyValue,
        ?string $stabilityAiApiValue,
        ?string $stableDiffusionApiKey,
        ?string $altTextAiApiValue,
        ?string $openAiAltTextApiKeyValue
    ): void {
        foreach (
            [
                $openAiApiKeyValue,
                $stabilityAiApiValue,
                $stableDiffusionApiKey,
                $altTextAiApiValue,
                $openAiAltTextApiKeyValue,
            ] as $apiKey
        ) {
            $this->addMessageAboutSavedApiKey($apiKey);
        }
    }

    private function validateApiCalls(
        SettingsDTO $openAi,
        SettingsDTO $stabilityAi,
        SettingsDTO $stableDiffusion,
        SettingsDTO $altTextAi,
        SettingsDTO $openAiAltText,
        SettingsDTO $summAi
    ): void {
        if (!empty($this->request->getParsedBody()['tx_mkcontentai_tools']['settingsRequestDTO']['openAiApiKeyValue'])) {
            $openAi->validateClientApiKey();
        }
        if (!empty($this->request->getParsedBody()['tx_mkcontentai_tools']['settingsRequestDTO']['stabilityAiApiValue'])) {
            $stabilityAi->validateClientApiKey();
        }
        if (!empty($this->request->getParsedBody()['tx_mkcontentai_tools']['settingsRequestDTO']['stableDiffusionAiApiValue'])) {
            $stableDiffusion->validateClientApiKey();
        }
        if (!empty($this->request->getParsedBody()['tx_mkcontentai_tools']['settingsRequestDTO']['altTextAiApiValue'])) {
            $altTextAi->validateClientApiKey();
        }
        if (!empty($this->request->getParsedBody()['tx_mkcontentai_tools']['settingsRequestDTO']['openAiAltTextApiKeyValue'])) {
            $openAiAltText->validateClientApiKey();
        }
        if (!empty($this->request->getParsedBody()['tx_mkcontentai_tools']['settingsRequestDTO']['summAiApiValue'])) {
            $summAi->validateClientApiKey();
        }
    }
}
