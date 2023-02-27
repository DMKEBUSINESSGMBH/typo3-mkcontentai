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
            'n' => 1,
            'size' => '256x256',
        ];

        $stream = curl_file_create(Environment::getPublicPath().$file->getOriginalResource()->getPublicUrl(), 'r');

        $array['image'] = $stream;
        var_dump(
            $openAi->createImageVariation($array)
        );

        return $this->htmlResponse();
    }

    public function listAction(): \Psr\Http\Message\ResponseInterface
    {
        return $this->htmlResponse();
    }

    /**
     * @return \Psr\Http\Message\ResponseInterface
     *
     * @throws \TYPO3\CMS\Core\Resource\Exception\ExistingTargetFileNameException
     */
    public function saveFileAction()
    {
        $openAi = new OpenAi('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');

        $array = [
            'prompt' => 'polish citizen in metro',
            'n' => 1,
            'size' => '256x256',
        ];

        $response = $openAi->image($array);
        if (is_string($response)) {
            $json = json_decode($response);

            $storageRepository = GeneralUtility::makeInstance(StorageRepository::class);
            $storage = $storageRepository->getDefaultStorage();
            if (!is_null($storage)) {
                $temporaryFile = GeneralUtility::tempnam('contentai');
                $fileResponse = file_get_contents($json->data[0]->url);
                if (is_string($fileResponse)) {
                    $temp = GeneralUtility::writeFileToTypo3tempDir(
                        $temporaryFile,
                        $fileResponse
                    );

                    $storage->addFile(
                        $temporaryFile,
                        $storage->getDefaultFolder(),
                        'new.png'
                    );
                }
            }
        }

        return $this->htmlResponse();
    }
}
