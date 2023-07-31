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

namespace DMK\MkContentAi\ViewHelpers;

use DMK\MkContentAi\Http\Client\ClientInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractConditionViewHelper;

class IfOperationAllowedViewHelper extends AbstractConditionViewHelper
{
    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('client', ClientInterface::class, 'Client object');
        $this->registerArgument('action', 'string', 'Action');
    }

    protected static function evaluateCondition($arguments = null)
    {
        if (!isset($arguments['client']) || !isset($arguments['action'])) {
            return false;
        }
        $client = $arguments['client'];
        $action = $arguments['action'];

        if (in_array($action, $client->getAllowedOperations())) {
            return true;
        }

        return false;
    }
}
