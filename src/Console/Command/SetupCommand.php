<?php

declare(strict_types=1);

namespace WPMigration\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class SetupCommand extends Command
{
    protected static $defaultName = 'setup';

    private string $rootPath;

    public function __construct(string $rootPath)
    {
        parent::__construct();
        $this->rootPath = rtrim($rootPath, '/');
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Initialize WP Migration Assistant environment')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Overwrite existing configuration');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('WP Migration Assistant Setup');

        $force = (bool) $input->getOption('force');

        // Step 1: Create required directories
        $io->section('Creating directories');
        $directories = [
            'var/migrations',
            'var/logs',
            'var/backups',
            'var/certs',
        ];

        foreach ($directories as $dir) {
            $fullPath = $this->rootPath . '/' . $dir;
            if (!is_dir($fullPath)) {
                if (mkdir($fullPath, 0755, true)) {
                    $io->text(sprintf('  <info>Created:</info> %s', $dir));
                } else {
                    $io->error(sprintf('Failed to create directory: %s', $dir));
                    return Command::FAILURE;
                }
            } else {
                $io->text(sprintf('  <comment>Exists:</comment> %s', $dir));
            }
        }

        // Step 2: Create .env file from template
        $io->section('Environment configuration');
        $envFile = $this->rootPath . '/.env';
        $envExample = $this->rootPath . '/.env.example';

        if (file_exists($envFile) && !$force) {
            $io->text('  <comment>Exists:</comment> .env (use --force to overwrite)');
        } else {
            if (file_exists($envExample)) {
                if (copy($envExample, $envFile)) {
                    $io->text('  <info>Created:</info> .env from .env.example');
                } else {
                    $io->warning('Failed to copy .env.example to .env');
                }
            } else {
                $io->text('  <comment>Skipped:</comment> .env.example not found');
            }
        }

        // Step 3: Verify system requirements
        $io->section('System requirements');
        $requirements = $this->checkRequirements();
        $allMet = true;

        foreach ($requirements as $name => $result) {
            if ($result['met']) {
                $io->text(sprintf('  <info>OK:</info> %s %s', $name, $result['version'] ?? ''));
            } else {
                $io->text(sprintf('  <error>Missing:</error> %s - %s', $name, $result['message']));
                $allMet = false;
            }
        }

        // Step 4: Verify PHP extensions
        $io->section('PHP extensions');
        $extensions = ['curl', 'json', 'mbstring', 'openssl', 'pdo', 'pdo_mysql', 'zip'];
        $extensionsMissing = [];

        foreach ($extensions as $ext) {
            if (extension_loaded($ext)) {
                $io->text(sprintf('  <info>OK:</info> %s', $ext));
            } else {
                $io->text(sprintf('  <error>Missing:</error> %s', $ext));
                $extensionsMissing[] = $ext;
            }
        }

        // Summary
        $io->newLine();
        if ($allMet && empty($extensionsMissing)) {
            $io->success('Setup completed successfully! WP Migration Assistant is ready to use.');
            return Command::SUCCESS;
        }

        $io->warning('Setup completed with warnings. Some optional dependencies are missing.');
        if (!empty($extensionsMissing)) {
            $io->text('Missing PHP extensions: ' . implode(', ', $extensionsMissing));
        }

        return Command::SUCCESS;
    }

    /** @return array<string, array{met: bool, version?: string, message?: string}> */
    private function checkRequirements(): array
    {
        $requirements = [];

        // PHP version
        $requirements['PHP 8.0+'] = [
            'met' => version_compare(PHP_VERSION, '8.0.0', '>='),
            'version' => PHP_VERSION,
            'message' => 'PHP 8.0 or higher is required',
        ];

        // rsync
        $rsyncOutput = [];
        $rsyncResult = 0;
        @exec('rsync --version 2>/dev/null', $rsyncOutput, $rsyncResult);
        $requirements['rsync'] = [
            'met' => $rsyncResult === 0,
            'version' => $rsyncResult === 0 ? $this->extractVersion($rsyncOutput[0] ?? '') : '',
            'message' => 'Install rsync: apt-get install rsync',
        ];

        // mysql-client
        $mysqlOutput = [];
        $mysqlResult = 0;
        @exec('mysql --version 2>/dev/null', $mysqlOutput, $mysqlResult);
        $requirements['mysql-client'] = [
            'met' => $mysqlResult === 0,
            'version' => $mysqlResult === 0 ? $this->extractVersion($mysqlOutput[0] ?? '') : '',
            'message' => 'Install mysql-client: apt-get install mysql-client',
        ];

        // ssh
        $sshOutput = [];
        $sshResult = 0;
        @exec('ssh -V 2>&1', $sshOutput, $sshResult);
        $requirements['openssh-client'] = [
            'met' => $sshResult === 0,
            'version' => !empty($sshOutput) ? $this->extractVersion($sshOutput[0] ?? '') : '',
            'message' => 'Install openssh-client: apt-get install openssh-client',
        ];

        return $requirements;
    }

    private function extractVersion(string $output): string
    {
        if (preg_match('/(\d+\.\d+(?:\.\d+)?(?:[._-]\w+)?)/', $output, $matches)) {
            return $matches[1];
        }

        return '';
    }
}
