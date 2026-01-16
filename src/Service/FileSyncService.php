<?php

declare(strict_types=1);

namespace WPMigration\Service;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

final class FileSyncService
{
    private Filesystem $filesystem;

    public function __construct()
    {
        $this->filesystem = new Filesystem();
    }

    public function sync(string $source, string $destination, bool $delete = false): void
    {
        if ($this->canRsync()) {
            $this->runRsync($source, $destination, $delete);
            return;
        }

        $this->filesystem->mirror($source, $destination, null, ['override' => true, 'delete' => $delete]);
    }

    private function canRsync(): bool
    {
        $process = new Process(['which', 'rsync']);
        $process->run();

        return $process->isSuccessful();
    }

    private function runRsync(string $source, string $destination, bool $delete): void
    {
        $command = ['rsync', '-az', rtrim($source, '/') . '/', rtrim($destination, '/') . '/'];
        if ($delete) {
            $command[] = '--delete';
        }

        $process = new Process($command);
        $process->setTimeout(600);
        $process->mustRun();
    }
}
