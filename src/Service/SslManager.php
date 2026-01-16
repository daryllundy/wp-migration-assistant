<?php

declare(strict_types=1);

namespace WPMigration\Service;

use Symfony\Component\Process\Process;

final class SslManager
{
    private string $certPath;

    public function __construct(string $certPath)
    {
        $this->certPath = rtrim($certPath, '/');
    }

    public function issueCertificate(string $domain, string $email): string
    {
        if (!is_dir($this->certPath)) {
            mkdir($this->certPath, 0755, true);
        }

        if ($this->hasCertbot()) {
            $process = new Process([
                'certbot',
                'certonly',
                '--standalone',
                '--non-interactive',
                '--agree-tos',
                '--email',
                $email,
                '-d',
                $domain,
            ]);
            $process->setTimeout(600);
            $process->mustRun();
            return 'letsencrypt';
        }

        $certFile = $this->certPath . '/' . $domain . '.pem';
        $keyFile = $this->certPath . '/' . $domain . '.key';

        $process = new Process([
            'openssl',
            'req',
            '-x509',
            '-nodes',
            '-newkey',
            'rsa:2048',
            '-keyout',
            $keyFile,
            '-out',
            $certFile,
            '-days',
            '365',
            '-subj',
            '/CN=' . $domain,
        ]);
        $process->setTimeout(60);
        $process->mustRun();

        return 'self-signed';
    }

    private function hasCertbot(): bool
    {
        $process = new Process(['which', 'certbot']);
        $process->run();

        return $process->isSuccessful();
    }
}
