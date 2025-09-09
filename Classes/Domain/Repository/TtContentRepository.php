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

namespace DMK\MkContentAi\Domain\Repository;

use DMK\MkContentAi\Domain\Model\TtContent;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

class TtContentRepository extends Repository
{
    /**
     * @return QueryResultInterface<TtContent>
     */
    public function findRelatedNews(int $newsUid): QueryResultInterface
    {
        $queryBuilder = $this->createQuery();
        $queryBuilder->getQuerySettings()->setRespectStoragePage(false);
        $queryBuilder->matching(
            $queryBuilder->equals('tx_news_related_news', $newsUid)
        );

        return $queryBuilder->execute();
    }
}
