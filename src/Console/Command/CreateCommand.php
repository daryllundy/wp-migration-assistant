<?php

declare(strict_types=1);

namespace WPMigration\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use WPMigration\Service\MigrationPlanner;
use WPMigration\Service\ProviderRegistry;
use WPMigration\Support\JsonFile;

final class CreateCommand extends Command
{
    protected static $defaultName = 'create';

    private ProviderRegistry $providers;
    private MigrationPlanner $planner;

    public function __construct(ProviderRegistry $providers, MigrationPlanner $planner)
    {
        parent::__construct();
        $this->providers = $providers;
        $this->planner = $planner;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Create a migration using a hosting provider profile')
            ->addOption('provider', null, InputOption::VALUE_REQUIRED, 'Provider slug')
            ->addOption('source', null, InputOption::VALUE_REQUIRED, 'Source URL/path')
            ->addOption('config', null, InputOption::VALUE_OPTIONAL, 'Provider config file')
            ->addOption('output', null, InputOption::VALUE_OPTIONAL, 'Plan output file', 'migration-plan.json');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $providerSlug = (string) $input->getOption('provider');
        $source = (string) $input->getOption('source');

        if ($providerSlug === '' || $source === '') {
            $io->error('Provide --provider and --source.');
            return Command::INVALID;
        }

        $provider = $this->providers->get($providerSlug);
        $config = [];
        $configPath = (string) $input->getOption('config');
        if ($configPath !== '') {
            $config = JsonFile::read($configPath);
        }

        $plan = $this->planner->plan(
            $this->normalizeLocation($source),
            [
                'provider' => $provider->getSlug(),
                'config' => $provider->buildConfig($config),
            ],
            'zero-downtime'
        );

        $outputPath = (string) $input->getOption('output');
        JsonFile::write($outputPath, $plan->toArray());
        $io->success(sprintf('Created plan for %s provider at %s', $provider->getName(), $outputPath));

        return Command::SUCCESS;
    }

    /** @return array<string, mixed> */
    private function normalizeLocation(string $value): array
    {
        if (is_dir($value)) {
            return [
                'path' => $value,
                'url' => $value,
            ];
        }

        return [
            'url' => $value,
        ];
    }
}
