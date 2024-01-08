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

return [
    'Mkcontentai' => [
        'parent' => 'system',
        'position' => [],
        'access' => 'user',
        'workspaces' => 'online',
        'path' => '/module/mkcontentai',
        'iconIdentifier' => 'mkcontentai',
        'icon' => 'EXT:mkcontentai/Resources/Public/Icons/Extension.svg',
        'labels' => 'LLL:EXT:mkcontentai/Resources/Private/Language/locallang_contentai.xlf',
        'extensionName' => 'Mkcontentai',
        'controllerActions' => [
            DMK\MkContentAi\Controller\AiImageController::class => [
                'filelist', 'variants', 'prompt', 'promptResult', 'saveFile', 'upscale', 'extend', 'cropAndExtend',
            ],
            DMK\MkContentAi\Controller\SettingsController::class => [
                'settings',
            ],
            \DMK\MkContentAi\Controller\AiTextController::class => [
                'altText', 'altTextSave',
            ],
        ],
    ],
];
