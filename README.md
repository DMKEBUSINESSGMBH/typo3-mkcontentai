# MK Content AI

![TYPO3 compatibility](https://img.shields.io/badge/TYPO3-11.5%20%7C%2012.4-orange?maxAge=3600&style=flat-square&logo=typo3)
[![Latest Stable Version](https://img.shields.io/packagist/v/dmk/mkcontentai.svg?maxAge=3600&style=flat-square&logo=composer)](https://packagist.org/packages/dmk/mkcontentai)
[![Total Downloads](https://img.shields.io/packagist/dt/dmk/mkcontentai.svg?maxAge=3600&style=flat-square)](https://packagist.org/packages/dmk/mkcontentai)
[![Build Status](https://img.shields.io/github/actions/workflow/status/DMKEBUSINESSGMBH/typo3-mkcontentai/php.yml?branch=12.4&maxAge=3600&style=flat-square&logo=github-actions)](https://github.com/DMKEBUSINESSGMBH/typo3-mkcontentai/actions?query=workflow%3A%22PHP+Checks%22)
[![License](https://img.shields.io/packagist/l/dmk/mkcontentai.svg?maxAge=3600&style=flat-square&logo=gnu)](https://packagist.org/packages/dmk/mkcontentai)

"mkcontentai" is a powerful TYPO3 extension that leverages the latest advancements in artificial intelligence to generate high-quality images for your website. By connecting to both the OpenAI API and stablediffusionapi.com API, this extension provides an intuitive image generation tool that allows you to easily create custom images by simply providing a prompt.

After generating an image, user can choose which image should be saved to a directory within the TYPO3 file system. These images can then be accessed and managed through the standard TYPO3 Filelist module. Simply navigate to the directory where the images are saved, and you can preview, edit, and use them as you would with any other image in TYPO3. This makes it easy to incorporate the generated images into your website or web application, without the need for any additional steps or plugins.

## Installation

1. Install the "mkcontentai" extension in the standard TYPO3 way.
2. Once the extension is installed, it will be accessible in the left menu in the TYPO3 backend.
3. Click on the "Content AI" option in the left menu to access the extension's features and start generating images.

## Functionalities

### Image generation
Generate high-quality images for your website using AI. This extension provides an image generation tool that allows you to create custom images by providing a prompt. With its intuitive interface, you can easily generate images that match your desired style or content by providing a text prompt. 
### Variants
Generate image variants of previously generated images. This feature is useful if you want to create multiple variations of an image without having to generate a new image from scratch each time.
### Upscale
Generate higher-resolution images from previously generated images. Currently it works only with OpenAI API and 256x256 or 512x512 images.
### Outpainting
Extending image with AI. Currently it works only with StabilityAI - it is possible to extend left,right,top,bottom part of image as well as zoom out.
### Alt text generation
Automatic generation of alternative text (alt text) for images by alttext.ai API. This functionality is designed to enhance web accessibility and SEO performance by providing descriptive alt text for images. This functionality is implemented in two places:

- Filelist module (context menu for given image)
- Content element (button next to alt text field for given image)
### Settings
The "Settings" section allows you to configure the AI platforms and APIs that the extension should use, as well as additional options for Stable Diffusion. Specifically, in the "Settings" section, you can:

- Choose which AI platform the extension should use: OpenAI or Stable Diffusion.
- Enter the API keys for both platforms that the extension should use to connect to the APIs.
- Choose the Stable Diffusion model that the extension should use to generate images.
- Adjust any other settings or parameters that the extension provides.

These settings can be adjusted according to your preferences and needs. It's important to ensure that the API keys are entered correctly to enable the extension to connect to the AI platforms and generate images successfully.

## Changelog

- 12.1.0: add automatic alt text generation functionality (alttext.ai API), refactor of translations (english/german) - move to xlf files
- 12.0.6: use dall-e-3 model from OpenAI, use stable-diffusion-xl-1024-v1-0 from StabilityAI, fix for TCA buttons
- 12.0.5: image generation from filelist, outpaintina and upscaling as context menu in filelist
- 12.0.2: add StabilityAI including upscaling, add outpainting, little cleanup and fixes for some warnings
- 12.0.1: update extension icon
- 12.0.0: initial release
