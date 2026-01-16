<?php

declare(strict_types=1);

namespace WPMigration\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use WPMigration\Service\MediaOptimizer;

final class OptimizeMediaCommand extends Command
{
    protected static $defaultName = 'optimize-media';

    private MediaOptimizer $optimizer;

    public function __construct(MediaOptimizer $optimizer)
    {
        parent::__construct();
        $this->optimizer = $optimizer;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Optimize media files during migration')
            ->addOption('source', null, InputOption::VALUE_REQUIRED, 'Local WordPress path');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $source = (string) $input->getOption('source');
        if ($source === '') {
            $io->error('Provide --source.');
            return Command::INVALID;
        }

        $result = $this->optimizer->optimize($source);
        $io->success(sprintf('Optimized %d media files', $result['optimized'] ?? 0));
        return Command::SUCCESS;
    }
}
