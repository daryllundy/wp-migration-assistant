<?php

declare(strict_types=1);

namespace WPMigration\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use WPMigration\Service\ProviderRegistry;

final class ProvidersCommand extends Command
{
    protected static $defaultName = 'providers';

    private ProviderRegistry $providers;

    public function __construct(ProviderRegistry $providers)
    {
        parent::__construct();
        $this->providers = $providers;
    }

    protected function configure(): void
    {
        $this->setDescription('List supported hosting providers');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $rows = [];
        foreach ($this->providers->all() as $provider) {
            $rows[] = [$provider->getSlug(), $provider->getName()];
        }

        $io->table(['Slug', 'Name'], $rows);
        return Command::SUCCESS;
    }
}
