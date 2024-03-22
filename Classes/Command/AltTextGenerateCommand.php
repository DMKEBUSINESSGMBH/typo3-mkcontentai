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

namespace DMK\MkContentAi\Command;

use DMK\MkContentAi\Service\AiAltTextService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 */
class AltTextGenerateCommand extends Command
{
    private AiAltTextService $aiAltTextService;

    public function __construct(AiAltTextService $aiAltTextService)
    {
        parent::__construct();

        $this->aiAltTextService = $aiAltTextService;
    }

    protected function configure(): void
    {
        $this->setDescription('Generate alt texts in specific directory - example: "1:/mkcontentai/openai')
            ->setHelp('Generate multiple AltTexts from specific images directory by scheduler')
            ->addArgument('folderName', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->aiAltTextService->saveAltTextsMetaData($this->aiAltTextService->getMultipleAltTextsForImages((string) $input->getArgument('folderName')));

        return Command::SUCCESS;
    }
}
