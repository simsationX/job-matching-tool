<?php

namespace App\Command;

use App\Entity\Candidate;
use App\Exception\NoMatchesToExportException;
use App\Repository\CandidateRepository;
use App\Service\CandidateJobMatchExporterService;
use App\Service\CandidateJobMatchService;
use App\Service\MatchReportMailerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:candidate-job-match',
    description: 'Match candidates and jobs.'
)]
class CandidateJobMatchCommand extends Command
{
    public function __construct(
        private readonly CandidateJobMatchService $matchService,
        private readonly CandidateJobMatchExporterService $exporterService,
        private readonly CandidateRepository $candidateRepository,
        private readonly MatchReportMailerService $mailerService,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('mode', InputArgument::REQUIRED, 'all | new | single')
            ->addArgument('candidateId', InputArgument::OPTIONAL, 'Candidate ID (required if mode=single)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $mode = $input->getArgument('mode');
        $candidateId = $input->getArgument('candidateId');

        $io = new SymfonyStyle($input, $output);

        $io->title('Start matching process...');

        $totalMatches = 0;
        $processed = 0;
        $candidatesToExport = [];

        if ($mode === 'single') {
            if (!$candidateId) {
                $io->error('You must provide a candidateId when using mode=single.');
                return Command::FAILURE;
            }

            /** @var Candidate $candidate */
            $candidate = $this->candidateRepository->find($candidateId);
            if (null === $candidate) {
                $io->error(sprintf('Candidate with ID %d not found.', $processed));
                return Command::FAILURE;
            }

            $totalMatches = $this->matchService->matchCandidate($candidate);
            $processed++;

            if ($totalMatches > 0) {
                $candidatesToExport[] = $candidateId;
            }
            $io->info(sprintf('Matched candidate with ID %d with %d jobs.', $candidateId, $totalMatches));
        } else {
            $batchSize = 100;
            $offset = 0;

            do {
                $candidates = $mode === 'new'
                    ? $this->candidateRepository->findCandidatesWithoutMatches($batchSize, $offset)
                    : $this->candidateRepository->findAllCandidates($batchSize, $offset);

                foreach ($candidates as $candidate) {
                    $matchesPerCandidate = $this->matchService->matchCandidate($candidate);
                    $totalMatches += $matchesPerCandidate;
                    $processed++;

                    if ($matchesPerCandidate > 0) {
                        $candidatesToExport[] = $candidate->getId();
                        $io->info(sprintf('Matched candidate with ID %d with %d jobs.', $candidate->getId(), $matchesPerCandidate));
                    }

                    if ($processed % 10 === 0) {
                        $io->info(sprintf('%s candidates processed so far...',$processed));
                    }
                }

                $this->entityManager->flush();
                $this->entityManager->clear();

                $offset += $batchSize;

            } while (!empty($candidates));
        }

        $io->success("Matching done. Total candidates processed: $processed, total matches: $totalMatches");

        if (empty($candidatesToExport)) {
            $io->info('No CSV report to create.');
            return Command::SUCCESS;
        }

        try {
            $io->info('Create CSV reports...');
            $exportedFiles = $this->exporterService->exportCandidates($candidatesToExport);

            foreach ($exportedFiles as $candidateId => $csvPath) {
                $io->info("Sending report for candidate #$candidateId...");
                $this->mailerService->sendReportForCandidate($csvPath, $candidateId);

                if (file_exists($csvPath)) {
                    try {
                        unlink($csvPath);
                        $io->info("Deleted CSV file: $csvPath");
                    } catch (\Throwable $e) {
                        $io->warning("Failed to delete CSV: {$csvPath} ({$e->getMessage()})");
                    }
                }
            }

            $io->success("All candidate reports sent successfully.");
        } catch (NoMatchesToExportException $e) {
            $io->info($e->getMessage());
        }

        $this->entityManager->flush();
        return Command::SUCCESS;
    }
}
