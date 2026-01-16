<?php

declare(strict_types=1);

namespace WPMigration\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use WPMigration\Service\CdnManager;

final class SetupCdnCommand extends Command
{
    protected static $defaultName = 'setup-cdn';

    private CdnManager $cdnManager;

    public function __construct(CdnManager $cdnManager)
    {
        parent::__construct();
        $this->cdnManager = $cdnManager;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Configure CDN integration')
            ->addOption('provider', null, InputOption::VALUE_REQUIRED, 'CDN provider')
            ->addOption('config', null, InputOption::VALUE_OPTIONAL, 'Config file');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $provider = (string) $input->getOption('provider');
        if ($provider === '') {
            $io->error('Provide --provider.');
            return Command::INVALID;
        }

        $config = [];
        $configPath = (string) $input->getOption('config');
        if ($configPath !== '' && file_exists($configPath)) {
            $config = json_decode(file_get_contents($configPath) ?: '[]', true) ?: [];
        }

        $this->cdnManager->configure($provider, $config);
        $io->success(sprintf('CDN configured for %s', $provider));
        return Command::SUCCESS;
    }
}
