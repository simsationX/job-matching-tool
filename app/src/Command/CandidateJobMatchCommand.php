<?php

namespace App\Command;

use App\Entity\MatchLog;
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
            ->addArgument('mode', InputArgument::REQUIRED, 'all oder new');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $mode = $input->getArgument('mode');
        $io = new SymfonyStyle($input, $output);

        $io->title('Start matching process...');

        $batchSize = 100;
        $offset = 0;
        $totalMatches = 0;
        $processed = 0;

        do {
            $candidates = $mode === 'new'
                ? $this->candidateRepository->findCandidatesWithoutMatches($batchSize, $offset)
                : $this->candidateRepository->findAllCandidates($batchSize, $offset);

            foreach ($candidates as $candidate) {
                $totalMatches += $this->matchService->matchCandidate($candidate);
                $processed++;

                if ($processed % 10 === 0) {
                    $io->info("$processed candidates processed so far...");
                }
            }

            $this->entityManager->flush();
            $this->entityManager->clear();

            $offset += $batchSize;

        } while (!empty($candidates));

        $io->success("Matching done. Total candidates processed: $processed, total matches: $totalMatches");

        $matchLog = new MatchLog();
        $matchLog->setRunAt(new \DateTimeImmutable());
        $this->entityManager->persist($matchLog);

        try {
            $io->info('Create CSV report...');
            $csvPath = $this->exporterService->exportAll();

            $io->info('Send CSV report...');
            $this->mailerService->sendReport($csvPath);

            $io->success("CSV report sent successfully.");
        } catch (NoMatchesToExportException $e) {
            $io->info($e->getMessage());
        }

        $matchLog->setExportedAt(new \DateTimeImmutable());

        $this->entityManager->flush();

        return Command::SUCCESS;
    }
}
