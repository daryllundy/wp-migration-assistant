<?php

declare(strict_types=1);

namespace WPMigration\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use WPMigration\Service\WebhookNotifier;

final class TestWebhookCommand extends Command
{
    protected static $defaultName = 'test-webhook';

    private WebhookNotifier $notifier;

    public function __construct(WebhookNotifier $notifier)
    {
        parent::__construct();
        $this->notifier = $notifier;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Send a test webhook')
            ->addOption('url', null, InputOption::VALUE_REQUIRED, 'Webhook URL');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $url = (string) $input->getOption('url');
        if ($url === '') {
            $io->error('Provide --url.');
            return Command::INVALID;
        }

        $this->notifier->register($url, ['test']);
        $this->notifier->notify('test', ['message' => 'Webhook test payload']);
        $io->success('Webhook test sent');

        return Command::SUCCESS;
    }
}
