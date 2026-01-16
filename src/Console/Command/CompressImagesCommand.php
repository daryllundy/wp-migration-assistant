<?php

declare(strict_types=1);

namespace WPMigration\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use WPMigration\Service\MediaOptimizer;

final class CompressImagesCommand extends Command
{
    protected static $defaultName = 'compress-images';

    private MediaOptimizer $optimizer;

    public function __construct(MediaOptimizer $optimizer)
    {
        parent::__construct();
        $this->optimizer = $optimizer;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Compress images during migration')
            ->addOption('path', null, InputOption::VALUE_REQUIRED, 'Image path')
            ->addOption('quality', null, InputOption::VALUE_OPTIONAL, 'Compression quality', '85')
            ->addOption('format', null, InputOption::VALUE_OPTIONAL, 'Output format', 'webp');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $path = (string) $input->getOption('path');
        if ($path === '') {
            $io->error('Provide --path.');
            return Command::INVALID;
        }

        $this->optimizer->compressImage(
            $path,
            (int) $input->getOption('quality'),
            (string) $input->getOption('format')
        );

        $io->success('Image compressed');
        return Command::SUCCESS;
    }
}
