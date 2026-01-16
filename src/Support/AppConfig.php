<?php

declare(strict_types=1);

namespace WPMigration\Support;

use Dotenv\Dotenv;

final class AppConfig
{
    private string $rootPath;
    /** @var array<string, string> */
    private array $env;

    public function __construct(string $rootPath)
    {
        $this->rootPath = rtrim($rootPath, '/');
        $this->env = $_ENV;

        $envPath = $this->path('.env');
        if (file_exists($envPath)) {
            $dotenv = Dotenv::createImmutable($this->rootPath);
            $dotenv->safeLoad();
            $this->env = array_merge($this->env, $_ENV);
        }
    }

    public function get(string $key, ?string $default = null): ?string
    {
        if (array_key_exists($key, $this->env)) {
            return (string) $this->env[$key];
        }

        $value = getenv($key);
        if ($value !== false) {
            return (string) $value;
        }

        return $default;
    }

    public function path(string $relative): string
    {
        return $this->rootPath . '/' . ltrim($relative, '/');
    }

    public function rootPath(): string
    {
        return $this->rootPath;
    }
}
