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

namespace DMK\MkContentAi\Service;

use DMK\MkContentAi\Domain\Model\Image;
use TYPO3\CMS\Core\Imaging\GraphicalFunctions;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

class ExtendService
{
    public GraphicalFunctions $graphicalFunctions;

    public function __construct()
    {
        $this->graphicalFunctions = GeneralUtility::makeInstance(GraphicalFunctions::class);
    }

    /**
     * @return array<string, int>
     */
    public function resolutionForExtendedImage(string $sourceImagePath, string $direction): array
    {
        $dimensions = $this->getImageDimensions(null, $sourceImagePath);
        $dimensions = (int) $dimensions['width'].'x'.(int) $dimensions['height'];

        if (!('256x256' == $dimensions || '512x512' == $dimensions || '1024x1024' == $dimensions)) {
            $translatedMessage = LocalizationUtility::translate('labelErrorApiImagesDimension', 'mkcontentai') ?? '';

            throw new \Exception($translatedMessage);
        }

        $newDimensions = [
            'width' => 0,
            'height' => 0,
        ];
        switch ($dimensions) {
            case '256x256':
                $newDimensions = [
                    'width' => 256,
                    'height' => 256,
                ];
                break;
            case '512x512':
                $newDimensions = [
                    'width' => 512,
                    'height' => 512,
                ];
                break;
            case '1024x1024':
                if ('zoomOut' == $direction) {
                    $translatedMessage = LocalizationUtility::translate('labelErrorApiImagesZoom', 'mkcontentai') ?? '';

                    throw new \Exception($translatedMessage);
                }

                $newDimensions = [
                    'width' => 1024,
                    'height' => 1024,
                ];
                break;
        }
        if ('zoomOut' == $direction) {
            $newDimensions['width'] = (int) $newDimensions['width'] * 2;
            $newDimensions['height'] = (int) $newDimensions['height'] * 2;
        }

        return $newDimensions;
    }

    /**
     * @return int[]
     *
     * @throws \Exception
     */
    private function getImageDimensions($source = null, string $imagePath = null): array
    {
        $width = null;
        $height = null;
        if (!$source && !$imagePath) {
            $translatedMessage = LocalizationUtility::translate('labelErrorApiImageSource', 'mkcontentai') ?? '';

            throw new \Exception($translatedMessage);
        }
        if ($imagePath) {
            $size = getimagesize($imagePath);
            if (false !== $size) {
                $width = $size[0];
                $height = $size[1];
            }
        }
        if ($source) {
            $width = imagesx($source);
            $height = imagesy($source);
        }

        if (!is_int($width) || !is_int($height)) {
            $translatedMessage = LocalizationUtility::translate('labelErrorApiImageIntegerDimension', 'mkcontentai') ?? '';

            throw new \Exception($translatedMessage);
        }

        return [
            'width' => $width,
            'height' => $height,
        ];
    }

    public function createMask($source, string $direction, int $widthExtended, int $heightExtended): string
    {
        $tempDir = sys_get_temp_dir();

        $maskImage = $tempDir.'/mask_'.$direction.'.png';

        $dest = imagecreatetruecolor($widthExtended, $heightExtended);
        if (false == $dest) {
            $translatedMessage = LocalizationUtility::translate('labelErrorApiResource', 'mkcontentai') ?? '';

            throw new \Exception($translatedMessage);
        }

        $dimensions = $this->getImageDimensions($source);

        imagealphablending($dest, false);
        imagesavealpha($dest, true);

        $transparentColor = imagecolorallocatealpha($dest, 0, 0, 0, 127);
        if (!is_int($transparentColor)) {
            $translatedMessage = LocalizationUtility::translate('labelErrorTransparentColor', 'mkcontentai') ?? '';

            throw new \Exception($translatedMessage);
        }

        switch ($direction) {
            case 'bottom':
                imagecopy($dest, $source, 0, 0, 0, $dimensions['height'] / 2, $dimensions['width'], $dimensions['height'] / 2);
                imagefill($dest, 0, $dimensions['height'] / 2, $transparentColor);
                break;
            case 'top':
                imagefill($dest, 0, 0, $transparentColor);
                imagecopy($dest, $source, 0, $dimensions['height'] / 2, 0, 0, $dimensions['width'], $dimensions['height'] / 2);
                break;
            case 'left':
                imagecopy($dest, $source, $dimensions['width'] / 2, 0, 0, 0, $dimensions['width'] / 2, $dimensions['height']);
                imagefill($dest, 0, 0, $transparentColor);
                break;
            case 'right':
                imagecopy($dest, $source, 0, 0, $dimensions['width'] / 2, 0, $dimensions['width'] / 2, $dimensions['height']);
                imagefill($dest, $dimensions['width'] / 2, 0, $transparentColor);
                break;
            case 'zoomOut':
                imagecopy($dest, $source, $dimensions['width'] / 2, $dimensions['height'] / 2, 0, 0, $dimensions['width'], $dimensions['height']);
                imagefill($dest, 0, 0, $transparentColor);
                break;
        }

        imagepng($dest, $maskImage);
        imagedestroy($dest);

        return $maskImage;
    }

    public function createCombined($source, $result, string $combined, string $direction): void
    {
        $dimensions = $this->getImageDimensions($source);

        $combinedImg = false;
        switch ($direction) {
            case 'bottom':
                $combinedImg = $this->combinedImage($dimensions['width'], (int) ($dimensions['height'] * 1.5));
                imagecopy($combinedImg, $source, 0, 0, 0, 0, $dimensions['width'], $dimensions['height']);
                imagecopy($combinedImg, $result, 0, $dimensions['height'], 0, $dimensions['height'] / 2, $dimensions['width'], $dimensions['height'] / 2);
                break;
            case 'top':
                $combinedImg = $this->combinedImage($dimensions['width'], (int) ($dimensions['height'] * 1.5));
                imagecopy($combinedImg, $result, 0, 0, 0, 0, $dimensions['width'], $dimensions['height'] / 2);
                imagecopy($combinedImg, $source, 0, $dimensions['height'] / 2, 0, 0, $dimensions['width'], $dimensions['height']);
                break;
            case 'left':
                $combinedImg = $this->combinedImage((int) ($dimensions['width'] * 1.5), $dimensions['height']);
                imagecopy($combinedImg, $result, 0, 0, 0, 0, $dimensions['width'] / 2, $dimensions['height']);
                imagecopy($combinedImg, $source, $dimensions['width'] / 2, 0, 0, 0, $dimensions['width'], $dimensions['height']);
                break;
            case 'right':
                $combinedImg = $this->combinedImage((int) ($dimensions['width'] * 1.5), $dimensions['height']);
                imagecopy($combinedImg, $source, 0, 0, 0, 0, $dimensions['width'], $dimensions['height']);
                imagecopy($combinedImg, $result, $dimensions['width'], 0, $dimensions['width'] / 2, 0, $dimensions['width'] / 2, $dimensions['height']);
                break;
            case 'zoomOut':
                break;
        }

        if (!$combinedImg) {
            $translatedMessage = LocalizationUtility::translate('labelErrorCombinedImage', 'mkcontentai') ?? '';

            throw new \Exception($translatedMessage);
        }

        imagepng($combinedImg, $combined);
        imagedestroy($combinedImg);
    }

    private function combinedImage(int $width, int $height)
    {
        $combinedImg = imagecreatetruecolor($width, $height);
        if (false == $combinedImg) {
            $translatedMessage = LocalizationUtility::translate('labelErrorTypeResource', 'mkcontentai') ?? '';

            throw new \Exception('$combinedImg '.$translatedMessage);
        }

        return $combinedImg;
    }

    /**
     * @param array<Image> $images
     *
     * @return array<Image>
     *
     * @throws \Exception
     */
    public function getImages(array $images, $source, string $direction): array
    {
        $tempDir = sys_get_temp_dir();
        $resultImage = $tempDir.'/result_'.$direction.'.png';
        $combinedImage = $tempDir.'/combined_'.$direction.'.png';

        $currentImage = current($images);
        if (false === $currentImage) {
            $translatedMessage = LocalizationUtility::translate('labelErrorGetImage', 'mkcontentai') ?? '';

            throw new \Exception($translatedMessage);
        }
        // combine images
        file_put_contents($resultImage, GeneralUtility::getUrl($currentImage->getUrl()));

        $result = imagecreatefrompng($resultImage);
        if (false == $result) {
            $translatedMessage = LocalizationUtility::translate('labelErrorTypeResource', 'mkcontentai') ?? '';

            throw new \Exception('$result '.$translatedMessage);
        }
        $this->createCombined($source, $result, $combinedImage, $direction);
        imagedestroy($result);

        $images = [];
        $content = GeneralUtility::getUrl($combinedImage);
        if (false === $content) {
            $translatedMessage = LocalizationUtility::translate('labelErrorReadCombinedImage', 'mkcontentai') ?? '';

            throw new \Exception($translatedMessage);
        }
        $translatedMessage = LocalizationUtility::translate('labelExtendedImage', 'mkcontentai') ?? '';
        $images[] = new Image($combinedImage, $translatedMessage, base64_encode($content));

        return $images;
    }
}
