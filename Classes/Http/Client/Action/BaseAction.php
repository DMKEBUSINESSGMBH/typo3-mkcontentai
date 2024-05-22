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

abstract class BaseAction
{
    /**
     * @return array<string,string>
     */
    abstract public function getActions(): array;

    /**
     * @param string[] $params
     */
    public function buildUrl(string $actionName, array $params = []): string
    {
        if (!array_key_exists($actionName, $this->getActions())) {
            throw new \Exception(sprintf('Action with name %s not found', $actionName));
        }

        $url = $this->getActions()[$actionName];

        return vsprintf($url, $params);
    }

    /**
     * @param string[] $params
     */
    public function buildFullUrl(string $apiLink, string $actionName, array $params = []): string
    {
        return $apiLink.$this->buildUrl($actionName, $params);
    }
}
