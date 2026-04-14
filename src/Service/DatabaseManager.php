<?php

declare(strict_types=1);

namespace WPMigration\Service;

use Doctrine\DBAL\DriverManager;
use Spatie\DbDumper\Databases\MySql;
use Symfony\Component\Process\Process;

final class DatabaseManager
{
    /** @param array<string, string> $config */
    public function dump(array $config, string $outputPath): void
    {
        $dumper = MySql::create()
            ->setHost($config['host'] ?? 'localhost')
            ->setDbName($config['name'] ?? '')
            ->setUserName($config['user'] ?? '')
            ->setPassword($config['password'] ?? '')
            ->useSingleTransaction()
            ->addExtraOption('--skip-comments');

        $dumper->dumpToFile($outputPath);
    }

    /** @param array<string, string> $config */
    public function import(array $config, string $inputPath): void
    {
        $command = [
            'mysql',
            '-h', $config['host'] ?? 'localhost',
            '-u', $config['user'] ?? '',
            $config['name'] ?? '',
        ];

        if (!empty($config['password'])) {
            $command[] = '-p' . $config['password'];
        }

        $stream = fopen($inputPath, 'rb');
        if ($stream === false) {
            throw new \RuntimeException('Unable to open database dump: ' . $inputPath);
        }

        $process = new Process($command);
        $process->setInput($stream);
        $process->setTimeout(600);

        try {
            $process->mustRun();
        } finally {
            fclose($stream);
        }
    }

    /** @param array<string, string> $config */
    public function optimize(array $config): array
    {
        $connection = DriverManager::getConnection([
            'dbname' => $config['name'] ?? '',
            'user' => $config['user'] ?? '',
            'password' => $config['password'] ?? '',
            'host' => $config['host'] ?? 'localhost',
            'driver' => 'pdo_mysql',
        ]);

        $tables = $connection->fetchFirstColumn('SHOW TABLES');
        foreach ($tables as $table) {
            $connection->executeStatement('OPTIMIZE TABLE ' . $table);
        }

        return [
            'tables_optimized' => count($tables),
        ];
    }
}
