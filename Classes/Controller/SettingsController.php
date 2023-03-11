<?php

namespace DMK\MkContentAi\Controller;

use DMK\MkContentAi\Http\Client\OpenAiClient;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class SettingsController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
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
        }
        $this->view->assignMultiple(
            [
                'apiKey' => $openAi->getApiKey(),
            ]
        );
    }
}
