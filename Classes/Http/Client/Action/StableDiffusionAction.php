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

class StableDiffusionAction extends BaseAction
{
    public const API_LINK = 'https://stablediffusionapi.com/api/v3/';
    public const DREAMBOOTH_API_LINK = 'https://stablediffusionapi.com/api/v4/dreambooth/';

    public function getActions(): array
    {
        return [
            'system_load' => 'system_load',
            'img2img' => 'img2img',
            'text2img' => 'text2img',
            'model_list' => 'model_list',
        ];
    }
}
