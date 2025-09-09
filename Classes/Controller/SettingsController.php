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

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
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
        $summAi = SettingsDTO::createSummAiClient(
            $settingsRequestDTO->getSummAiApiValue(),
            $settingsRequestDTO->getSummAiUserEmail(),
            $settingsRequestDTO->getNewsContentTypes(),
            $settingsRequestDTO->getAvailableNewsContentTypes(),
            $settingsRequestDTO->getSummAiAppendedContentUid(),
            $settingsRequestDTO->getSummAiDevMode(),
            $settingsRequestDTO->getSummAiDisclaimer()
        );
        /** @var SummAiClient $summAiClient */
        $summAiClient = $summAi->getClient();

        try {
            $this->validateApiCalls($openAi, $stabilityAi, $stableDiffusion, $altTextAi, $summAi);
            $summAiClient->setEmail($summAiClient->checkEmailFromRequest($settingsRequestDTO->getSummAiUserEmail()), $validateSumAiEmail);
            $summAiClient->setNewsContentTypes($summAiClient->checkNewsContentTypesFromRequest($settingsRequestDTO->getNewsContentTypes()));
            $summAiClient->setSummAiAppendedContentUid($summAiClient->checkAppendedContentUidFromRequest($settingsRequestDTO->getSummAiAppendedContentUid()));
            $summAiClient->setSummAiDevMode($summAiClient->checkDevModeFromRequest($settingsRequestDTO->getSummAiDevMode()));
            $summAiClient->setSummAiDisclaimer($summAiClient->checkSummAiDisclaimerFromRequest($settingsRequestDTO->getSummAiDisclaimer()));
            $modelList = $stableDiffusion->getClient()->modelList();
        } catch (\Exception $e) {
            $this->addFlashMessage($e->getMessage(), '', AbstractMessage::ERROR, false);
            $modelList = [];
        }
        $this->addMessagesAboutSavedApiKeys($settingsRequestDTO->getOpenAiApiKeyValue(), $settingsRequestDTO->getStabilityAiApiValue(), $stableDiffusionApiKey, $settingsRequestDTO->getAltTextAiApiValue());

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
        $settingsRequestDTO->setAvailableNewsContentTypes($this->extractNewsContentTypes());
        $settingsRequestDTO->setNewsContentTypes($summAiClient->getNewsContentTypes());
        $settingsRequestDTO->setSummAiAppendedContentUid($summAiClient->getSummAiAppendedContentUid());
        $settingsRequestDTO->setSummAiDevMode($summAiClient->isSummAiDevMode());
        $settingsRequestDTO->setSummAiDisclaimer($summAiClient->showSummAiDisclaimer());
        try {
            $this->view->assignMultiple(
                [
                    'openAi' => $openAi,
                    'stableDiffusion' => $stableDiffusion,
                    'stabilityAi' => $stabilityAi,
                    'altTextAi' => $altTextAi,
                    'summAi' => $summAi,
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

    private function addMessagesAboutSavedApiKeys(?string $openAiApiKeyValue, ?string $stabilityAiApiValue, ?string $stableDiffusionApiKey, ?string $altTextAiApiValue): void
    {
        foreach (
            [
                $openAiApiKeyValue,
                $stabilityAiApiValue,
                $stableDiffusionApiKey,
                $altTextAiApiValue,
            ] as $apiKey
        ) {
            $this->addMessageAboutSavedApiKey($apiKey);
        }
    }

    private function validateApiCalls(SettingsDTO $openAi, SettingsDTO $stabilityAi, SettingsDTO $stableDiffusion, SettingsDTO $altTextAi, SettingsDTO $summAi): void
    {
        $openAi->validateClientApiKey();
        $stabilityAi->validateClientApiKey();
        $stableDiffusion->validateClientApiKey();
        $altTextAi->validateClientApiKey();
        $summAi->validateClientApiKey();
    }

    /**
     * @return list<string>
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    private function extractNewsContentTypes(): array
    {
        $availableNewsContentTypes = [];
        foreach ($GLOBALS['TCA']['tt_content']['columns']['CType']['config']['items'] as $cType) {
            $availableNewsContentTypes[] = $cType[1];
        }

        return $availableNewsContentTypes;
    }
}
