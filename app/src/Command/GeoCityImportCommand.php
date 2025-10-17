<?php
// src/Command/GeoCityImportCommand.php
namespace App\Command;

use App\Service\GeoCityImportService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:geo-city-import',
    description: 'Import geo cities from CSV.'
)]
class GeoCityImportCommand extends Command
{
    public function __construct(private GeoCityImportService $importService)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('csvPath', InputArgument::REQUIRED, 'Path to CSV file with GeoCities');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $csvPath = $input->getArgument('csvPath');

        $io->title('GeoCities CSV Import');
        $io->note("File: $csvPath");

        try {
            $this->importService->importCsv($csvPath);
        } catch (\Throwable $e) {
            $io->error("Error: " . $e->getMessage());
            return Command::FAILURE;
        }

        $io->success('Import done!');
        return Command::SUCCESS;
    }
}
