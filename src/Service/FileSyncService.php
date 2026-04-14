<?php

declare(strict_types=1);

namespace WPMigration\Service;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

final class FileSyncService
{
    private Filesystem $filesystem;
    private bool $rsyncAvailable;

    public function __construct()
    {
        $this->filesystem = new Filesystem();
        $this->rsyncAvailable = $this->detectRsync();
    }

    /** @return array<string, int|string> */
    public function sync(string $source, string $destination, bool $delete = false): array
    {
        if ($this->rsyncAvailable) {
            return $this->runRsync($source, $destination, $delete);
        }

        $this->filesystem->mirror($source, $destination, null, ['override' => true, 'delete' => $delete]);

        return [
            'files_transferred' => $this->countFiles($source),
            'bytes_transferred' => $this->directorySize($source),
            'batches' => 1,
            'mode' => 'mirror',
        ];
    }

    /** @return array<string, int|string> */
    public function syncInChunks(string $source, string $destination, int $chunkSizeBytes, bool $delete = false): array
    {
        $this->filesystem->mkdir($destination);
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        $filesTransferred = 0;
        $bytesTransferred = 0;
        $batches = 0;
        $currentBatchBytes = 0;
        $seenFiles = [];

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }

            $sourcePath = $file->getPathname();
            $relativePath = ltrim(substr($sourcePath, strlen(rtrim($source, DIRECTORY_SEPARATOR))), DIRECTORY_SEPARATOR);
            $seenFiles[$relativePath] = true;

            if ($currentBatchBytes > 0 && ($currentBatchBytes + $file->getSize()) > $chunkSizeBytes) {
                $batches++;
                $currentBatchBytes = 0;
            }

            $destinationPath = rtrim($destination, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $relativePath;
            $this->filesystem->mkdir(dirname($destinationPath));
            $this->filesystem->copy($sourcePath, $destinationPath, true);

            $filesTransferred++;
            $bytesTransferred += $file->getSize();
            $currentBatchBytes += $file->getSize();
        }

        if ($filesTransferred > 0) {
            $batches++;
        }

        if ($delete) {
            $this->removeMissingFiles($destination, $seenFiles);
        }

        return [
            'files_transferred' => $filesTransferred,
            'bytes_transferred' => $bytesTransferred,
            'batches' => $batches,
            'mode' => 'chunked',
        ];
    }

    private function detectRsync(): bool
    {
        $process = new Process(['which', 'rsync']);
        $process->run();

        return $process->isSuccessful();
    }

    /** @return array<string, int|string> */
    private function runRsync(string $source, string $destination, bool $delete): array
    {
        $command = ['rsync', '-az', '--stats', rtrim($source, '/') . '/', rtrim($destination, '/') . '/'];
        if ($delete) {
            $command[] = '--delete';
        }

        $process = new Process($command);
        $process->setTimeout(600);
        $process->mustRun();

        return [
            'files_transferred' => $this->extractIntStat($process->getOutput(), 'Number of regular files transferred'),
            'bytes_transferred' => $this->extractIntStat($process->getOutput(), 'Total transferred file size'),
            'batches' => 1,
            'mode' => 'rsync',
        ];
    }

    /** @param array<string, bool> $seenFiles */
    private function removeMissingFiles(string $destination, array $seenFiles): void
    {
        if (!is_dir($destination)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($destination, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            $path = $file->getPathname();
            $relativePath = ltrim(substr($path, strlen(rtrim($destination, DIRECTORY_SEPARATOR))), DIRECTORY_SEPARATOR);

            if ($file->isFile() && !isset($seenFiles[$relativePath])) {
                $this->filesystem->remove($path);
                continue;
            }

            if ($file->isDir()) {
                $children = scandir($path);
                if ($children !== false && count($children) === 2) {
                    $this->filesystem->remove($path);
                }
            }
        }
    }

    private function countFiles(string $path): int
    {
        if (!is_dir($path)) {
            return 0;
        }

        $count = 0;
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $count++;
            }
        }

        return $count;
    }

    private function directorySize(string $path): int
    {
        if (!is_dir($path)) {
            return 0;
        }

        $size = 0;
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $size += $file->getSize();
            }
        }

        return $size;
    }

    private function extractIntStat(string $output, string $label): int
    {
        if (preg_match('/' . preg_quote($label, '/') . ':\s*([0-9,]+)/i', $output, $matches) !== 1) {
            return 0;
        }

        return (int) str_replace(',', '', $matches[1]);
    }
}
