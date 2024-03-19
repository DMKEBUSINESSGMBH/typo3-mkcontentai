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

namespace DMK\MkContentAi\Utility;

use TYPO3\CMS\Core\Context\Context;

class PermissionsUtility
{
    private Context $context;

    public function injectContext(Context $context): void
    {
        $this->context = $context;
    }

    /**
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public function userHasAccessToSettings(): bool
    {
        $user = $this->context->getAspect('backend.user');
        if ($user->isAdmin()) {
            return true;
        }

        return true === $GLOBALS['BE_USER']->check('custom_options', 'mkcontentaiSettingsPermissions:settingsPermissions');
    }
}
