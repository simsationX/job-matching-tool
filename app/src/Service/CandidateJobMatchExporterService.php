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

    public function exportAll(): string
    {
        $matches = $this->candidateJobMatchRepository->findAllForExport();

        if (!$matches) {
            throw new NoMatchesToExportException('No matches to export.');
        }

        return $this->exportMatches($matches, 'all');
    }

    public function exportForCandidate(Candidate $candidate): string
    {
        $matches = $this->candidateJobMatchRepository->findBy([
            'candidate' => $candidate,
            'status' => 'active',
            'exported' => false,
        ]);

        if (!$matches) {
            throw new NoMatchesToExportException('No matches for this candidate.');
        }

        return $this->exportMatches($matches, 'candidate_' . $candidate->getId());
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
            if (is_array($match)) {
                fputcsv($fp, [
                    $match['candidate_id'],
                    $match['candidate_name'],
                    $match['candidate_location'],
                    $match['candidate_additional_locations'],
                    $match['candidate_consultant'],
                    $match['job_company'],
                    $match['job_website'],
                    $match['job_position'],
                    $match['job_description'],
                    $match['job_location'],
                    number_format((float)$match['score'], 2, '.', ''),
                    $match['found_at']?->format('Y-m-d H:i:s') ?? '',
                ]);

                $entity = $this->candidateJobMatchRepository->find($match['id'] ?? null);
                if ($entity) {
                    $entity->setExported(true);
                }
            } else {
                // falls Entity
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
        }

        fclose($fp);
        $this->entityManager->flush();

        return $csvPath;
    }
}
