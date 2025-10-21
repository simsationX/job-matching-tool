<?php

namespace App\Service;

use AllowDynamicProperties;
use App\Entity\Candidate;
use App\Entity\CandidateJobMatch;
use App\Entity\Job;
use App\Repository\CandidateJobMatchRepository;
use App\Repository\JobRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Process\Process;

#[AllowDynamicProperties]
class CandidateJobMatchService
{
    public function __construct(
        private readonly CandidateJobMatchRepository $candidateJobMatchRepository,
        private readonly CandidateKeywordService $candidateKeywordService,
        private readonly JobRepository $jobRepository,
        private readonly JobLocationResolverService $jobLocationResolverService,
        private readonly EntityManagerInterface $entityManager,
        private readonly ParameterBagInterface $params,
    ) {
        $this->fallbackJobs = $this->jobRepository->findBy([
            'location' => ['Deutschland', 'Germany', 'Allemagne', null]
        ]);
    }

    public function matchCandidate(Candidate $candidate): int
    {
        ini_set('memory_limit', '-1');

        $candidateLocations = array_filter(array_merge(
            [$candidate->getLocation()],
            explode(',', $candidate->getAdditionalLocations() ?? '')
        ));

        $geoCities = [];
        foreach ($candidateLocations as $location) {
            $resolved = $this->jobLocationResolverService->resolve($location);
            foreach ($resolved as $city) {
                if (!in_array($city, $geoCities, true)) {
                    $geoCities[] = $city;
                }
            }
        }

        $candidateJobs = [];

        foreach ($geoCities as $geoCity) {
            $jobsWithinRadius = $this->jobRepository->findJobsWithinRadius(
                $geoCity->getLatitude(),
                $geoCity->getLongitude(),
                50
            );

            foreach ($jobsWithinRadius as $job) {
                if (!array_key_exists($job->getId(), $candidateJobs)) {
                    $candidateJobs[$job->getId()] = $job;
                }
            }
        }

        $candidateJobs = array_merge($candidateJobs, $this->fallbackJobs);

        $matches = $this->calculateMatches($candidate, $candidateJobs);
        $matches = array_slice($matches, 0, 10);

        $saved = 0;
        $existingMatches = $this->candidateJobMatchRepository->findBy(['candidate' => $candidate]);

        $existingMap = [];
        foreach ($existingMatches as $existingMatch) {
            $key = $existingMatch->getPositionId() . '-' . $existingMatch->getAdId();
            $existingMap[$key] = $existingMatch;
        }

        $newKeys = [];
        foreach ($matches as $match) {
            $updated = false;
            $job = $match['job'];
            $key = $job->getPositionId() . '-' . $job->getAdId();
            $newKeys[] = $key;

            /** @var CandidateJobMatch|null $candidateJobMatch */
            $candidateJobMatch = $existingMap[$key] ?? null;

            if (null === $candidateJobMatch) {
                $candidateJobMatch = new CandidateJobMatch();
                $candidateJobMatch->setFoundAt(new \DateTimeImmutable());
                $candidateJobMatch->setExported(false);
                $updated = true;
            }

            if (abs($candidateJobMatch->getScore() - (float)$match['score']) > 0.0001
                || $candidateJobMatch->getPosition() !== $job->getPosition()
                || $candidateJobMatch->getLocation() !== $job->getLocation()
                || $candidateJobMatch->getCompany() !== $job->getCompany()
                || $candidateJobMatch->getDescription() !== $job->getDescription()
            ) {
                $candidateJobMatch
                    ->setCandidate($candidate)
                    ->setCompany($job->getCompany())
                    ->setWebsite($job->getWebsite())
                    ->setCompanyPhone($job->getCompanyPhone())
                    ->setContactEmail($job->getContactEmail())
                    ->setContactPerson($job->getContactPerson())
                    ->setContactPhone($job->getContactPhone())
                    ->setLocation($job->getLocation())
                    ->setPosition($job->getPosition())
                    ->setPositionId($job->getPositionId())
                    ->setAdId($job->getAdId())
                    ->setDescription($job->getDescription())
                    ->setScore($match['score'])
                    ->setExported(false);
                $updated = true;
            }

            if ($updated) {
                $this->entityManager->persist($candidateJobMatch);
                $saved++;
            }
        }

        foreach ($existingMatches as $existingMatch) {
            $key = $existingMatch->getPositionId() . '-' . $existingMatch->getAdId();
            if (!in_array($key, $newKeys, true)) {
                $this->entityManager->remove($existingMatch);
            }
        }

        $this->entityManager->flush();

        return $saved;
    }

    /**
     * @return Job[]
     */
    private function calculateMatches(Candidate $candidate, array $jobs): array
    {
        $results = [];
        $keywords = $this->candidateKeywordService->extractCandidateKeywords($candidate);

        $candidateText = implode(' ', $keywords);
        $faissScores = $this->getFaissScores($candidateText, $jobs);

        foreach ($jobs as $job) {
            $score = 0;
            $position = mb_strtolower($job->getPosition() ?? '');
            $description = mb_strtolower($job->getDescription() ?? '');

            foreach ($keywords as $kw) {
                $kw = mb_strtolower(trim($kw));
                if ($kw === '') {
                    continue;
                }

                if (preg_match('/\b' . preg_quote($kw, '/') . '\b/u', $position)) {
                    $score += 15;
                } elseif (stripos($position, $kw) !== false) {
                    $score += 8;
                }

                if (preg_match('/\b' . preg_quote($kw, '/') . '\b/u', $description)) {
                    $score += 10;
                } elseif (stripos($description, $kw) !== false) {
                    $score += 4;
                }
            }

            $candidateLocations = [];
            foreach (array_merge([$candidate->getLocation()], explode(',', $candidate->getAdditionalLocations() ?? '')) as $loc) {
                $loc = trim($loc);
                if ($loc !== '' && !in_array($loc, $candidateLocations, true)) {
                    $candidateLocations[] = $loc;
                }
            }

            foreach ($candidateLocations as $locationKeyword) {
                if (preg_match('/\b' . preg_quote($locationKeyword, '/') . '\b/u', $position)) {
                    $score += 4;
                } elseif (stripos($position, $locationKeyword) !== false) {
                    $score += 2;
                }

                if (preg_match('/\b' . preg_quote($locationKeyword, '/') . '\b/u', $description)) {
                    $score += 2;
                } elseif (stripos($description, $locationKeyword) !== false) {
                    ++$score;
                }
            }

            $faissScore = $faissScores[$job->getId()] ?? 0;
            $score *= (1 + $faissScore);

            $results[] = ['job' => $job, 'score' => $score];
        }

        usort($results, fn($a, $b) => $b['score'] <=> $a['score']);

        return $results;
    }

    private function getFaissScores(string $candidateText, array $jobs): array
    {
        $projectDir = $this->params->get('kernel.project_dir');
        $faissIndexFile = $projectDir . '/data/jobs.index';
        $jobIdsFile = $projectDir . '/data/job_ids.pkl';

        $filteredJobIdsFile = tempnam(sys_get_temp_dir(), 'filtered_jobs_');
        $filteredJobIds = array_map(fn(Job $job) => $job->getId(), $jobs);
        file_put_contents($filteredJobIdsFile, json_encode($filteredJobIds));

        try {
            $process = new Process([
                'python3',
                $projectDir . '/scripts/match_jobs.py',
                $candidateText,
                $faissIndexFile,
                $jobIdsFile,
                $filteredJobIdsFile,
            ]);
            $process->setTimeout(null);
            $process->mustRun();

            $output = json_decode($process->getOutput(), true, 512, JSON_THROW_ON_ERROR);

            if (!empty($output) && isset($output[0]['id'])) {
                $assoc = [];
                foreach ($output as $entry) {
                    $assoc[(int)$entry['id']] = (float)$entry['score'];
                }
                return $assoc;
            }

            return $output;

        } finally {
            unlink($filteredJobIdsFile);
        }
    }
}
