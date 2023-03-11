<?php

namespace DMK\MkContentAi\Http\Client;

use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class OpenAiClient implements ClientInterface
{
    public const KEY_NAME = 'open_ai_api_key';

    public Registry $registry;

    public function __construct()
    {
        $this->registry = GeneralUtility::makeInstance(Registry::class);
        $this->getApiKey();
    }

    /**
     * @throws \Exception
     */
    public function validate(): void
    {
        $apiKey = $this->getApiKey();
        if (empty($apiKey)) {
            throw new \Exception('OpenAI API KEY not settled.');
        }
    }

    public function getApiKey(): string
    {
        return strval($this->registry->get(self::class, self::KEY_NAME));
    }

    public function setApiKey(string $apiKey): void
    {
        $this->registry->set(self::class, self::KEY_NAME, $apiKey);
    }
}
