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

namespace DMK\MkContentAi\Controller;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;

class BaseController extends ActionController
{
    public function initializeAction(): void
    {
        $pageRenderer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Page\PageRenderer::class);
        $pageRenderer->loadRequireJsModule('TYPO3/CMS/Mkcontentai/MkContentAi');
    }

    public function initializeView(ViewInterface $view): void
    {
        $typo3Version = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Information\Typo3Version::class);
        $this->view->assign('TYPO3MajorVersion', $typo3Version->getMajorVersion());
        parent::initializeView($view);
    }
}
