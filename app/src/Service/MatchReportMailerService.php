<?php

namespace App\Service;

use App\Entity\Candidate;
use App\Repository\CandidateJobMatchRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

final class MatchReportMailerService
{
    private const CANDIDATE_JOB_MATCH_URL = "https://job-matching.bullheads.de/candidate-job-match";

    public function __construct(
        private MailerInterface $mailer,
        private CandidateJobMatchRepository $candidateJobMatchRepository,
        private EntityManagerInterface $entityManager,
    ) {}

    public function sendReportForCandidate(int $candidateId): void
    {
        $matches = $this->candidateJobMatchRepository->findUnexportedMatchesForCandidate($candidateId);

        if (empty($matches)) {
            return;
        }

        /** @var Candidate $candidate */
        $candidate = $matches[0]->getCandidate();

        $jobListHtml = '';
        foreach ($matches as $match) {
            $jobListHtml .= sprintf(' - <a href="%s/%s">%s/%s</a><br/>',
                self::CANDIDATE_JOB_MATCH_URL,
                $match->getId(),
                self::CANDIDATE_JOB_MATCH_URL,
                $match->getId()
            );
            $match->setExported(true);
            $this->entityManager->persist($match);
        }

        $this->entityManager->flush();

        $email = (new Email())
            ->from('info@bullheads.de')
            ->to($candidate->getConsultant()?->getEmail() ?? 'info@bullheads.de')
            ->subject(sprintf('Neue Jobmatches für %s (%s)', $candidate->getName(), $candidateId))
            ->html(sprintf(
                'Hallo %s,<br/><br/>
Hier sind die aktuellen Job-Matches für <strong>%s (%s)</strong>:<br/><br/>
%s<br/>
Beste Grüße<br/>Bullheads',
                $candidate->getConsultant()?->getName() ?? 'Bullheads',
                $candidate->getName(),
                $candidateId,
                $jobListHtml
            ), 'text/html');

        $this->mailer->send($email);
    }
}
