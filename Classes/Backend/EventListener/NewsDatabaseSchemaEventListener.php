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

namespace DMK\MkContentAi\Backend\EventListener;

use TYPO3\CMS\Core\Database\Event\AlterTableDefinitionStatementsEvent;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/**
 * Add required EXT:news columns to database schema if EXT:news is installed.
 */
final class NewsDatabaseSchemaEventListener
{
    public function __invoke(AlterTableDefinitionStatementsEvent $event): void
    {
        if (ExtensionManagementUtility::isLoaded('news')) {
            $event->addSqlData($this->generateNewsSqlData());
        }
    }

    private function generateNewsSqlData(): string
    {
        return <<<SQL
CREATE TABLE tx_news_domain_model_news
(
    tx_mkcontentai_original_news int(11) unsigned NOT NULL DEFAULT '0',
    tx_mkcontentai_translated_news int(11) unsigned NOT NULL DEFAULT '0'
);
SQL;
    }
}
