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

namespace DMK\MkContentAi\User\InlineControl;

use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ImageGenerationButton
{
    /**
     * @var NodeFactory
     */
    protected $nodeFactory;

    /**
     * @var IconFactory
     */
    protected $iconFactory;

    public function __construct()
    {
        $this->nodeFactory = GeneralUtility::makeInstance(NodeFactory::class);
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
    }

    /**
     * @param array{
     *     resultArray: array{
     *         requireJsModules: list<JavaScriptModuleInstruction>,
     *     },
     * } $parameters
     */
    public function render(array $parameters): string
    {
        $item = ' <div class="form-control-wrap"><button type="button" class="btn btn-default t3js-prompt" id="prompt">';
        $item .= $this->iconFactory->getIcon('actions-image', Icon::SIZE_SMALL)->render().' ';
        $item .= htmlspecialchars('AI generation of image by text prompt');
        $item .= '</button></div>';

        $parameters['resultArray']['requireJsModules'][] = JavaScriptModuleInstruction::forRequireJS('TYPO3/CMS/Mkcontentai/BackendPrompt');

        return $item;
    }
}
