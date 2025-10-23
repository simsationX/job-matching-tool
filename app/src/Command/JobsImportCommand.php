<?php

namespace App\Command;

use App\Service\JobImportService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AsCommand(
    name: 'app:jobs-import',
    description: 'Import new jobs.',
)]
class JobsImportCommand extends Command
{
    public function __construct(
        private ParameterBagInterface $params,
        private JobImportService $jobImportService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Start job import');

        $projectDir = $this->params->get('kernel.project_dir');
        $filePath = $projectDir.'/public/uploads/job_import.xlsx';
        if (!file_exists($filePath)) {
            $io->error('No import file found.: ' . $filePath);
            return Command::FAILURE;
        }

        $count = $this->jobImportService->import($filePath);
        $io->success("Import done. $count jobs successfully imported.");

        unlink($filePath);

        return Command::SUCCESS;
    }
}
