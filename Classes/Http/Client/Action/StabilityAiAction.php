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

namespace DMK\MkContentAi\Http\Client\Action;

class StabilityAiAction extends BaseAction
{
    public const API_LINK = 'https://api.stability.ai/';

    public function getActions(): array
    {
        return [
            'image' => 'v1/generation/stable-diffusion-xl-1024-v1-0/text-to-image',
            'imageVariation' => 'v1/generation/stable-diffusion-xl-1024-v1-0/image-to-image',
            'account' => 'v1/user/account',
            'upscale' => 'v1/generation/esrgan-v1-x2plus/image-to-image/upscale',
            'extend' => 'v2beta/stable-image/edit/outpaint',
        ];
    }
}
