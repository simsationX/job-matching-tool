<?php
namespace App\Command;

use App\Repository\JobRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Process\Process;

#[AsCommand(
    name: 'app:embed-jobs',
    description: 'Embed jobs for semantic translater.',
)]
class EmbedJobsCommand extends Command
{
    public function __construct(
        private JobRepository $jobRepository,
        private EntityManagerInterface $entityManager,
        private ParameterBagInterface $params,
    ) {
        parent::__construct();
    }

    /**
     * @throws \JsonException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $newJobsCount = $this->jobRepository->countJobsCreatedLast24h();

        if ($newJobsCount === 0) {
            $io->info('No new jobs. Stop embedding jobs.');
            return Command::SUCCESS; // oder Command::FAILURE, wenn du das signalisieren willst
        }

        $io->info(sprintf('%d new jobs found', $newJobsCount));
        $io->title('Start embedding jobs...');

        $batchSize = 1000;
        $offset = 0;
        $total = 0;
        $projectDir = $this->params->get('kernel.project_dir');
        $dataDir = $projectDir . '/data';
        if (!is_dir($dataDir)) {
            if (!mkdir($dataDir, 0777, true) && !is_dir($dataDir)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $dataDir));
            }
        }

        $faissIndexFile = $dataDir . '/jobs.index';
        $jobIdsFile = $dataDir . '/job_ids.pkl';

        if (file_exists($faissIndexFile)) {
            unlink($faissIndexFile);
        }
        if (file_exists($jobIdsFile)) {
            unlink($jobIdsFile);
        }

        do {
            $jobs = $this->jobRepository->findBatch($offset, $batchSize);
            $total += count($jobs);

            if (empty($jobs)) {
                break;
            }

            $batchFile = tempnam(sys_get_temp_dir(), "jobs_batch_{$offset}.json");
            file_put_contents($batchFile, json_encode($jobs, JSON_THROW_ON_ERROR));

            try {
                $process = new Process([
                    'python3',
                    $projectDir . '/scripts/encode_batch.py',
                    $batchFile,
                    $faissIndexFile,
                    $jobIdsFile
                ]);
                $process->setTimeout(null);
                $process->mustRun();

                $output->writeln("Batch {$offset} encoded");
            } finally {
                unlink($batchFile);
            }

            $this->entityManager->clear();
            $offset += $batchSize;
        } while (true);

        $io->success(sprintf('Embedded %d jobs successfully.', $total));

        return Command::SUCCESS;
    }
}
