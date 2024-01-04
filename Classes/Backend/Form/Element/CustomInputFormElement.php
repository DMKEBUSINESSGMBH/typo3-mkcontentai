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

namespace DMK\MkContentAi\Backend\Form\Element;

use TYPO3\CMS\Backend\Form\Element\InputTextElement;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class CustomInputFormElement extends InputTextElement
{
    /**
     * @return array<string, mixed>
     */
    public function render(): array
    {
        $resultArray = parent::render();

        if ('sys_file_reference' !== $this->data['tableName'] || 'alternative' !== $this->data['fieldName']) {
            return $resultArray;
        }

        $html = explode(LF, $resultArray['html']);
        $fileUid = $this->data['databaseRow']['uid_local'][0]['uid'];
        $pageLanguageUid = $this->data['databaseRow']['sys_language_uid'];

        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $item[] = ' <div data-uid-local="'.$fileUid.'" data-sys-language-uid="'.$pageLanguageUid.'"class="formengine-field-item t3js-formengine-field-item form-description">
 <button type="button" class="btn btn-default t3js-prompt  alt-refresh">
 <span class="spinner-border spinner-border-sm" style="display: none"></span>';
        $item[] = $iconFactory->getIcon('actions-image', Icon::SIZE_SMALL)->render().' ';
        $item[] = htmlspecialchars('Generate alt text by AI');
        $item[] = '</button></div>';

        array_splice($html, 3, 0, $item);
        $resultArray['html'] = implode(LF, $html);
        $resultArray['requireJsModules'][] = JavaScriptModuleInstruction::forRequireJS('TYPO3/CMS/Mkcontentai/AltText');

        return $resultArray;
    }
}
