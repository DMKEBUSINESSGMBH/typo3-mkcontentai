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

class AltTextAction extends BaseAction
{
    public const API_LINK = 'https://alttext.ai/api/';

    public function getActions(): array
    {
        return [
            'altText' => 'v1/images',
            'altTextByAssetId' => 'v1/images/%s',
            'account' => 'v1/account',
        ];
    }
}
