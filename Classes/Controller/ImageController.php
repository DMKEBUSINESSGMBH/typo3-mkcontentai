<?php

declare(strict_types=1);

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

use Orhanerday\OpenAi\OpenAi;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Resource\StorageRepository;
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
class ImageController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
{
    /**
     * action index.
     */
    public function indexAction(): \Psr\Http\Message\ResponseInterface
    {
        return $this->htmlResponse();
    }

    /**
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function uploadForVariantAction(File $file)
    {
        $openAi = new OpenAi('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');

        $array = [
            'image' => $file->getOriginalResource()->getContents(),
            'n' => 2,
            'size' => '256x256',
        ];

        $stream = curl_file_create(Environment::getPublicPath().$file->getOriginalResource()->getPublicUrl(), 'r');

        $array['image'] = $stream;

        $response = $openAi->createImageVariation($array);
        if (is_string($response)) {
            $json = json_decode($response);
            $this->view->assignMultiple(
                [
                    'json' => $json,
                    'originalFile' => $file,
                ]
            );
        }

        return $this->htmlResponse();
    }

    public function listAction(): \Psr\Http\Message\ResponseInterface
    {
        return $this->htmlResponse();
    }

    /**
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function generateImagePromptAction()
    {
        return $this->htmlResponse();
    }

    /**
     * @return \Psr\Http\Message\ResponseInterface
     *
     * @throws \TYPO3\CMS\Core\Resource\Exception\ExistingTargetFileNameException
     */
    public function chooseGeneratedImageAction(string $text)
    {
        $openAi = new OpenAi('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');

        $array = [
            'prompt' => $text,
            'n' => 2,
            'size' => '256x256',
        ];

        $response = $openAi->image($array);
        if (is_string($response)) {
            $json = json_decode($response);
            $this->view->assignMultiple(
                [
                    'json' => $json,
                    'text' => $text,
                ]
            );
        }

        return $this->htmlResponse();
    }

    /**
     * @return void
     *
     * @throws \TYPO3\CMS\Core\Resource\Exception\ExistingTargetFileNameException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     */
    public function saveFileAction(string $imageUrl, string $description)
    {
        $storageRepository = GeneralUtility::makeInstance(StorageRepository::class);
        $storage = $storageRepository->getDefaultStorage();
        if (!is_null($storage)) {
            $temporaryFile = GeneralUtility::tempnam('contentai');
            $fileResponse = file_get_contents($imageUrl);
            if (is_string($fileResponse)) {
                GeneralUtility::writeFileToTypo3tempDir(
                    $temporaryFile,
                    $fileResponse
                );

                /** @var \TYPO3\CMS\Core\Resource\File $fileObject */
                $fileObject = $storage->addFile(
                    $temporaryFile,
                    $storage->getDefaultFolder(),
                    time().'.png'
                );

                $metaData = $fileObject->getMetaData();
                $metaData->offsetSet('description', $description);
                $metaData->save();

                $this->addFlashMessage('File has been saved');
            }
        }

        $this->redirect('list');
    }
}
