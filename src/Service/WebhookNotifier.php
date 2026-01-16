<?php

declare(strict_types=1);

namespace WPMigration\Service;

use GuzzleHttp\ClientInterface;
use WPMigration\Support\JsonFile;

final class WebhookNotifier
{
    private ClientInterface $httpClient;
    private string $storagePath;

    public function __construct(ClientInterface $httpClient, string $storagePath)
    {
        $this->httpClient = $httpClient;
        $this->storagePath = $storagePath;
    }

    /** @param array<int, string> $events */
    public function register(string $url, array $events): void
    {
        $payload = JsonFile::read($this->storagePath);
        $payload[] = [
            'url' => $url,
            'events' => $events,
        ];
        JsonFile::write($this->storagePath, $payload);
    }

    /** @param array<string, mixed> $payload */
    public function notify(string $event, array $payload): void
    {
        $webhooks = JsonFile::read($this->storagePath);
        foreach ($webhooks as $webhook) {
            if (!in_array($event, $webhook['events'] ?? [], true)) {
                continue;
            }

            $this->httpClient->request('POST', $webhook['url'], [
                'json' => [
                    'event' => $event,
                    'payload' => $payload,
                ],
            ]);
        }
    }
}
