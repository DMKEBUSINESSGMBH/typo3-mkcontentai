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

namespace DMK\MkContentAi\Backend\Template\Components\Buttons;

use TYPO3\CMS\Backend\Template\Components\Buttons\ButtonInterface;

class CustomButton implements ButtonInterface
{
    private string $htmlSource;

    public function setHtmlSource(string $htmlSource): ButtonInterface
    {
        $this->htmlSource = $htmlSource;

        return $this;
    }

    public function getType(): string
    {
        return static::class;
    }

    public function isValid(): bool
    {
        if (
            '' !== trim($this->getHtmlSource())
            && self::class === $this->getType()
        ) {
            return true;
        }

        return false;
    }

    public function __toString(): string
    {
        return $this->render();
    }

    public function render(): string
    {
        return $this->getHtmlSource();
    }

    public function getHtmlSource(): string
    {
        return $this->htmlSource;
    }
}
