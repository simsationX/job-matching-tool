<?php

namespace App\Service;

use App\Entity\Candidate;
use App\Repository\CandidateRepository;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class MatchReportMailerService
{
    public function __construct(
        private MailerInterface $mailer,
        private CandidateRepository $candidateRepository
    ) {}

    public function sendReportForCandidate(string $csvPath, int $candidateId): void
    {
        /** @var Candidate $candidate */
        $candidate = $this->candidateRepository->find($candidateId);
        if (null === $candidate) {
            return;
        }

        $email = (new Email())
            ->from('info@bullheads.de')
            ->to($candidate->getConsultant()?->getEmail() ?? 'info@bullheads.de')
            ->subject(sprintf('Neue Jobmatches für %s (%s)', $candidate->getName(), $candidateId))
            ->html(sprintf(
                'Hallo %s,<br/><br/>anbei das aktuelle CSV mit den neuen Job-Matches für <strong>%s (%s)</strong>.<br/><br/>Beste Grüße<br/>',
                    $candidate->getConsultant()?->getName() ?? 'Bullheads',
                    $candidate->getName(),
                    $candidateId
                )
            )
            ->attachFromPath($csvPath);

        $this->mailer->send($email);
    }
}
