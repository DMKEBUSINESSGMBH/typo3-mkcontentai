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

namespace DMK\MkContentAi;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Just a Dummy Class from Skeleton. Simply Remove it :).
 *
 * @author Michael Wagner
 */
class Dummy
{
    public function getClassHash(): int
    {
        return GeneralUtility::md5int(self::class);
    }
}
