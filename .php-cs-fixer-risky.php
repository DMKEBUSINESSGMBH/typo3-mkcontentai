<?php

$header = <<<'EOF'
Copyright notice

(c) DMK E-BUSINESS GmbH <dev@dmk-ebusiness.de>
All rights reserved

This file is part of TYPO3 CMS-based extension "mkcontentai" by DMK E-BUSINESS GmbH.

It is free software; you can redistribute it and/or modify it under
the terms of the GNU General Public License, either version 2
of the License, or any later version.
EOF;

$config = new \PhpCsFixer\Config();

return $config
    ->setCacheFile('var/.php-cs-fixer-risky.cache')
    ->setFinder(\PhpCsFixer\Finder::create()->in('Classes'))
    ->setRules([
        '@PSR2' => true,
        'declare_strict_types' => true,
    ])
    ->setLineEnding("\n");
