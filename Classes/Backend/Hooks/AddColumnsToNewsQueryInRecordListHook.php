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

namespace DMK\MkContentAi\Backend\Hooks;

use TYPO3\CMS\Core\Database\Query\QueryBuilder;

/**
 * Add additional EXT:news columns to database queries in record list
 * to allow further checks in combination with translation to easy or plain language.
 */
final class AddColumnsToNewsQueryInRecordListHook
{
    /**
     * @param array<string, mixed> $parameters
     * @param string[]             $additionalConstraints
     * @param string[]             $fields
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function modifyQuery(
        array $parameters,
        string $table,
        int $pageId,
        array $additionalConstraints,
        array $fields,
        QueryBuilder $queryBuilder
    ): void {
        // Early return if all fields are selected or news table is not queried
        if ($fields === ['*'] || 'tx_news_domain_model_news' !== $table) {
            return;
        }

        if (!in_array('tx_mkcontentai_original_news', $fields, true)) {
            $queryBuilder->addSelect('tx_mkcontentai_original_news');
        }

        if (!in_array('tx_mkcontentai_translated_news', $fields, true)) {
            $queryBuilder->addSelect('tx_mkcontentai_translated_news');
        }
    }
}
