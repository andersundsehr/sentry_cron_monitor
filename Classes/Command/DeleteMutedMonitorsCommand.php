<?php

declare(strict_types=1);

namespace AUS\SentryCronMonitor\Command;

use AUS\SentryCronMonitor\Service\MutedMonitorService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Core\Environment;

#[AsCommand(
    name: 'sentry-cron-monitor:delete-muted',
    description: 'Deletes muted Sentry cron monitors for the configured project.',
)]
final class DeleteMutedMonitorsCommand extends Command
{
    public function __construct(private readonly MutedMonitorService $mutedMonitorService)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'force',
            'f',
            InputOption::VALUE_NONE,
            'Delete the listed muted monitors. Without this option, the command only previews them.',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        if (!Environment::getContext()->isProduction()) {
            $io->error('Muted monitors can only be deleted in a TYPO3 Production context.');

            return self::FAILURE;
        }

        $mutedMonitors = $this->mutedMonitorService->findMutedMonitors();
        if ($mutedMonitors === []) {
            $io->success('No muted Sentry cron monitors found for the configured project.');

            return self::SUCCESS;
        }

        $io->table(
            ['ID', 'Slug', 'Name'],
            array_map(
                static fn (array $monitor): array => [$monitor['id'], $monitor['slug'], $monitor['name']],
                $mutedMonitors,
            ),
        );

        if ($input->getOption('force') !== true) {
            $io->note('Preview only. Run the command again with --force to delete these monitors.');

            return self::SUCCESS;
        }

        foreach ($mutedMonitors as $monitor) {
            $this->mutedMonitorService->deleteMonitor($monitor['id']);
        }

        $io->success(sprintf('Deleted %d muted Sentry cron monitor(s).', count($mutedMonitors)));

        return self::SUCCESS;
    }
}
