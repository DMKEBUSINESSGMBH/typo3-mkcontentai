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

namespace DMK\MkContentAi\Domain\Model;

/**
 * This file is part of the "DMK Content AI" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * (c) 2023
 */

/**
 * Image.
 */
class Image extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
{
    public function __construct(string $url, string $text = '', string $base64 = '')
    {
        $this->setUrl($url);
        $this->setText($text);
        $this->setBase64($base64);
    }

    /**
     * @var string
     */
    protected $text = '';

    /**
     * @var string
     */
    protected $url;

    /**
     * @var string
     */
    protected $base64 = '';

    /**
     * @return string
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * @return void
     */
    public function setText(string $text)
    {
        $this->text = $text;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): void
    {
        $this->url = $url;
    }

    public function getBase64(): string
    {
        return $this->base64;
    }

    public function setBase64(string $base64): void
    {
        $this->base64 = $base64;
    }
}
