<?php

declare(strict_types=1);

namespace WPMigration\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use WPMigration\Service\WebhookNotifier;

final class WebhookCommand extends Command
{
    protected static $defaultName = 'webhook';

    private WebhookNotifier $notifier;

    public function __construct(WebhookNotifier $notifier)
    {
        parent::__construct();
        $this->notifier = $notifier;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Register webhook for migration events')
            ->addOption('url', null, InputOption::VALUE_REQUIRED, 'Webhook URL')
            ->addOption('events', null, InputOption::VALUE_REQUIRED, 'Comma-separated events');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $url = (string) $input->getOption('url');
        $events = (string) $input->getOption('events');

        if ($url === '' || $events === '') {
            $io->error('Provide --url and --events.');
            return Command::INVALID;
        }

        $eventList = array_map('trim', explode(',', $events));
        $this->notifier->register($url, $eventList);
        $io->success('Webhook registered');

        return Command::SUCCESS;
    }
}
