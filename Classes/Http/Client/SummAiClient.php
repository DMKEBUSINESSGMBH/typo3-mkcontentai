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

namespace DMK\MkContentAi\Http\Client;

use DMK\MkContentAi\Http\Client\Action\SummAiAction;
use Symfony\Component\HttpClient\HttpClient;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class SummAiClient extends BaseClient implements ClientInterface
{
    /**
     * @var \Symfony\Contracts\HttpClient\HttpClientInterface
     */
    private $client;

    private SummAiAction $summAiAction;

    /**
     * @var FlashMessageService
     */
    public $flashMessageService;

    public function __construct()
    {
        $this->getApiKey();
        $this->getUserEmail();
        $this->getNewsContentTypes();
        $this->getSummAiAppendedContentUid();
        $this->isSummAiDevMode();
        $this->showSummAiDisclaimer();

        $this->client = HttpClient::create();
    }

    public function injectSummAiAction(SummAiAction $summAiAction): void
    {
        $this->summAiAction = $summAiAction;
    }

    public function getTestApiCall(): \stdClass
    {
        $response = $this->client->request('GET', $this->summAiAction->buildFullUrl($this->summAiAction::API_LINK, 'glossary', []),
            [
                'headers' => $this->getPreparedHeaderRequest(),
            ]
        );
        $response = $this->validateResponse($response->getContent(false));

        return $response;
    }

    /**
     * @param string|bool $response
     *
     * @throws \Exception
     */
    public function validateResponse($response): \stdClass
    {
        if (!is_string($response)) {
            $translatedMessage = LocalizationUtility::translate('labelErrorApiResponseString', 'mkcontentai') ?? '';

            throw new \Exception($translatedMessage);
        }
        $response = json_decode($response);

        if (isset($response->detail)) {
            throw new \Exception($response->detail);
        }

        return $response;
    }

    public function sendContentToTranslate(string $inputText, string $userEmail, string $inputTextType, string $outputLanguageLvl, string $separator): \stdClass
    {
        $formData = $this->prepareDataRequest($inputText, $userEmail, $inputTextType, $outputLanguageLvl, $separator);
        $response = $this->client->request('POST', $this->summAiAction->buildFullUrl($this->summAiAction::API_LINK, 'translation', []),
            [
                'headers' => $this->getPreparedHeaderRequest(),
                'json' => $formData,
            ]
        );

        $response = $this->validateResponse($response->getContent(false));

        return $response;
    }

    public function getAuthorizationHeader(): string
    {
        return 'Api-Key '.$this->getApiKey();
    }

    /**
     * @return array<string,string>
     */
    public function getPreparedHeaderRequest(): array
    {
        return [
            'Authorization' => $this->getAuthorizationHeader(),
            'Content-Type' => 'application/json',
        ];
    }

    /**
     * @return array{
     *     input_text: string,
     *     user: string,
     *     input_text_type: string,
     *     output_language_level: string,
     *     separator: string,
     *     is_test: bool,
     * }
     */
    public function prepareDataRequest(string $inputText, string $userEmail, string $inputTextType, string $outputLanguageLvl, string $separator): array
    {
        return [
            'input_text' => $inputText,
            'user' => $userEmail,
            'input_text_type' => $inputTextType,
            'output_language_level' => $outputLanguageLvl,
            'separator' => $separator,
            'is_test' => $this->isSummAiDevMode(),
        ];
    }

    public function setUserEmail(string $userEmail): void
    {
        $registry = $this->getRegistry();
        $class = $this->getClass();
        $registry->set($class, 'userEmail', $userEmail);
    }

    public function getUserEmail(): string
    {
        $registry = $this->getRegistry();
        $class = $this->getClass();

        return strval($registry->get($class, 'userEmail'));
    }

    public function validateUserEmail(string $email): bool
    {
        return false !== filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    public function validateApiKey(): bool
    {
        if ($this->validateUserEmail($this->getUserEmail()) && parent::validateApiKey()) {
            return true;
        }

        return false;
    }

    public function setEmail(string $email, bool $validateMail): void
    {
        if ($validateMail && !$this->validateUserEmail($email)) {
            $this->flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
            $translatedMessage = LocalizationUtility::translate('labelErrorEmail', 'mkcontentai') ?? '';
            $message = GeneralUtility::makeInstance(
                FlashMessage::class,
                $translatedMessage,
                '',
                FlashMessage::ERROR,
                false
            );
            $messageQueue = $this->flashMessageService->getMessageQueueByIdentifier();
            $messageQueue->addMessage($message);

            return;
        }

        try {
            $this->setUserEmail($email);
            $this->getTestApiCall();
        } catch (\Exception $e) {
            return;
        }
    }

    public function checkEmailFromRequest(?string $summAiUserEmail): string
    {
        return empty($summAiUserEmail) ? $this->getUserEmail() : $summAiUserEmail;
    }

    /**
     * @param list<string>|null $newsContentTypes
     *
     * @return list<string>
     */
    public function checkNewsContentTypesFromRequest(?array $newsContentTypes): array
    {
        return null === $newsContentTypes ? $this->getNewsContentTypes() : $newsContentTypes;
    }

    public function checkAppendedContentUidFromRequest(?int $summAiAppendedContentUid): ?int
    {
        return null === $summAiAppendedContentUid ? $this->getSummAiAppendedContentUid() : $summAiAppendedContentUid;
    }

    public function checkDevModeFromRequest(?bool $summAiDevMode): bool
    {
        return null === $summAiDevMode ? $this->isSummAiDevMode() : $summAiDevMode;
    }

    public function checkSummAiDisclaimerFromRequest(?bool $summAiDisclaimer): bool
    {
        return null === $summAiDisclaimer ? $this->showSummAiDisclaimer() : $summAiDisclaimer;
    }

    /**
     * @return list<string>
     */
    public function getNewsContentTypes(): array
    {
        $registry = $this->getRegistry();
        $class = $this->getClass();

        return $registry->get($class, 'newsContentTypes') ?? [];
    }

    /**
     * @param list<string> $newsContentTypes
     */
    public function setNewsContentTypes(array $newsContentTypes): void
    {
        try {
            $registry = $this->getRegistry();
            $class = $this->getClass();
            $registry->set($class, 'newsContentTypes', $newsContentTypes);
        } catch (\Exception $e) {
            return;
        }
    }

    public function getSummAiAppendedContentUid(): ?int
    {
        $registry = $this->getRegistry();
        $class = $this->getClass();

        return $registry->get($class, 'summAiAppendedContentUid');
    }

    public function setSummAiAppendedContentUid(?int $summAiAppendedContentUid): void
    {
        try {
            $registry = $this->getRegistry();
            $class = $this->getClass();
            $registry->set($class, 'summAiAppendedContentUid', $summAiAppendedContentUid);
        } catch (\Exception $e) {
            return;
        }
    }

    public function isSummAiDevMode(): bool
    {
        $registry = $this->getRegistry();
        $class = $this->getClass();

        return $registry->get($class, 'summAiDevMode') ?? false;
    }

    public function setSummAiDevMode(bool $summAiDevMode): void
    {
        try {
            $registry = $this->getRegistry();
            $class = $this->getClass();
            $registry->set($class, 'summAiDevMode', $summAiDevMode);
        } catch (\Exception $e) {
            return;
        }
    }

    public function showSummAiDisclaimer(): bool
    {
        $registry = $this->getRegistry();
        $class = $this->getClass();

        return $registry->get($class, 'summAiDisclaimer') ?? true;
    }

    public function setSummAiDisclaimer(bool $summAiDisclaimer): void
    {
        try {
            $registry = $this->getRegistry();
            $class = $this->getClass();
            $registry->set($class, 'summAiDisclaimer', $summAiDisclaimer);
        } catch (\Exception $e) {
            return;
        }
    }
}
