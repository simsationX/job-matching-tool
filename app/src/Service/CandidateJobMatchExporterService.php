<?php
// src/Service/CandidateJobMatchExporter.php
namespace App\Service;

use App\Exception\NoMatchesToExportException;
use App\Repository\CandidateJobMatchRepository;
use App\Repository\MatchLogRepository;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;

class CandidateJobMatchExporterService
{
    public function __construct(
        private MatchLogRepository $matchLogRepository,
        private CandidateJobMatchRepository $candidateJobMatchRepository,
        private ParameterBagInterface $params,
    ) {}

    public function exportAll(): string
    {
        $lastExportedAt = $this->matchLogRepository->findOneBy([], ['exportedAt' => 'DESC'])?->getExportedAt();

        $matches = $this->candidateJobMatchRepository->findAllBeforeExport($lastExportedAt);

        if (empty($matches)) {
            throw new NoMatchesToExportException('No new candidate job matches to export.');
        }

        $projectDir = $this->params->get('kernel.project_dir');
        $baseDir = sprintf('%s/var/exports', $projectDir);
        $csvPath = sprintf('%s/candidate_job_matches_%s.csv', $baseDir, date('Y-m-d_His'));

        (new Filesystem())->mkdir($baseDir);

        $fp = fopen($csvPath, 'wb');
        fputcsv($fp, [
            'candidate_id',
            'candidate_name',
            'candidate_location',
            'candidate_additional_locations',
            'candidate_consultant',
            'job_company',
            'job_website',
            'job_position',
            'job_description',
            'job_location',
            'score',
            'found_at'
        ]);

        foreach ($matches as $row) {
            $row['score'] = number_format((float)$row['score'], 2, '.', '');
            $row['found_at'] = $row['found_at'] ? $row['found_at']->format('Y-m-d H:i:s') : '';
            fputcsv($fp, $row);
        }

        fclose($fp);

        return $csvPath;
    }
}
