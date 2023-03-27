<?php

declare(strict_types=1);

/*
 * Copyright notice
 *
 * (c) DMK E-BUSINESS GmbH <dev@dmk-ebusiness.de>
 * All rights reserved
 *
 * This file is part of TYPO3 CMS-based extension "container" by b13.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

namespace DMK\MkContentAi\Tests\Unit;

/*
 * This file is part of TYPO3 CMS-based extension "container" by b13.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

use DMK\MkContentAi\Domain\Model\Image;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ImageTest extends UnitTestCase
{
    private ?Image $image = null;
    private $url = 'http://test.de/img.png';
    private $text = 'text';

    protected function setUp(): void
    {
        $this->image = new Image($this->url, $this->text);
    }

    /**
     * @test
     */
    public function getText(): void
    {
        $this->assertSame(
            $this->text,
            $this->image->getText()
        );
    }

    /**
     * @test
     */
    public function getUrl(): void
    {
        $this->assertSame(
            $this->url,
            $this->image->getUrl()
        );
    }
}
