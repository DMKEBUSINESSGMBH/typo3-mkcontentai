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

use DMK\MkContentAi\Dummy;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Just a Dummy Test from Skeleton. Simply Remove it :).
 *
 * @author Michael Wagner
 */
class DummyTest extends UnitTestCase
{
    private ?Dummy $stub = null;

    protected function setUp(): void
    {
        $this->stub = new Dummy();
    }

    /**
     * @test
     */
    public function getClassHash(): void
    {
        $this->assertSame(
            94726372,
            $this->stub->getClassHash()
        );
    }
}
