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

namespace DMK\MkContentAi\DTO;

use TYPO3\CMS\Core\Resource\File;

class FileAltTextDTO
{
    private string $uid;

    private ?string $altText;

    private ?File $file;

    public function __construct(string $uid, ?string $altText, ?File $file = null)
    {
        $this->uid = $uid;
        $this->altText = $altText;
        $this->file = $file;
    }

    public static function fromArray(string $uid, ?string $altText): self
    {
        return new self($uid, $altText);
    }

    public function getUid(): string
    {
        return $this->uid;
    }

    public function getAltText(): ?string
    {
        return $this->altText;
    }

    public function setAltText(?string $altText): void
    {
        $this->altText = $altText;
    }

    public function getFile(): ?File
    {
        return $this->file;
    }

    public function setFile(?File $file): void
    {
        $this->file = $file;
    }
}
