<?php
// src/Service/CandidateJobMatchExporter.php
namespace App\Service;

use App\Entity\Candidate;
use App\Entity\CandidateJobMatch;
use App\Exception\NoMatchesToExportException;
use App\Repository\CandidateJobMatchRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;

class CandidateJobMatchExporterService
{
    public function __construct(
        private CandidateJobMatchRepository $candidateJobMatchRepository,
        private EntityManagerInterface $entityManager,
        private ParameterBagInterface $params,
    ) {}

    public function exportCandidates(array $candidateIds): array
    {
        $exportedFiles = [];

        foreach ($candidateIds as $candidateId) {
            $matches = $this->candidateJobMatchRepository->findUnexportedMatchesForCandidate($candidateId);

            if (empty($matches)) {
                continue;
            }

            $csvPath = $this->exportMatches($matches, 'candidate_' . $candidateId);
            $exportedFiles[$candidateId] = $csvPath;
        }

        return $exportedFiles;
    }

    private function exportMatches(array $matches, string $suffix): string
    {
        $projectDir = $this->params->get('kernel.project_dir');
        $baseDir = sprintf('%s/var/exports', $projectDir);
        (new Filesystem())->mkdir($baseDir);

        $csvPath = sprintf('%s/candidate_job_matches_%s_%s.csv', $baseDir, $suffix, date('Y-m-d_His'));
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

        foreach ($matches as $match) {
            fputcsv($fp, [
                $match->getCandidate()->getId(),
                $match->getCandidate()->getName(),
                $match->getCandidate()->getLocation(),
                $match->getCandidate()->getAdditionalLocations(),
                $match->getCandidate()->getConsultant()?->getName(),
                $match->getCompany(),
                $match->getWebsite(),
                $match->getPosition(),
                $match->getDescription(),
                $match->getLocation(),
                number_format($match->getScore(), 2, '.', ''),
                $match->getFoundAt()?->format('Y-m-d H:i:s') ?? '',
            ]);

            $match->setExported(true);
        }

        fclose($fp);
        $this->entityManager->flush();

        return $csvPath;
    }
}
