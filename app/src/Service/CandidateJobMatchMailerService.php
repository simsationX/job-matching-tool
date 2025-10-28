<?php
namespace App\Service;

use App\Entity\Candidate;
use App\Entity\CandidateJobMatch;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;

final class CandidateJobMatchMailerService
{
    public function __construct(
        private MailerInterface $mailer,
        private EntityManagerInterface $entityManager,
    ) {}

    public function sendMatches(Candidate $candidate, array $matches): array
    {
        $results = [
            'success' => 0,
            'alreadySent' => 0,
            'noEmail' => 0,
            'errors' => [],
        ];

        $candidateEmail = $candidate->getEmail();
        if (!$candidateEmail) {
            $results['noEmail'] = count($matches);
            return $results;
        }

        foreach ($matches as $match) {
            /** @var CandidateJobMatch $match */
            if ($match->getSentAt()) {
                $results['alreadySent']++;
                continue;
            }

            $fromEmail = $candidate->getConsultant()?->getEmail() ?? 'info@bullheads.de';
            $jobDescription = $match->getDescription(); // Plaintext
            $jobDescriptionHtml = nl2br(htmlspecialchars($jobDescription));

            $email = (new TemplatedEmail())
                ->from($fromEmail)
                ->to($candidateEmail)
                ->subject('Ihr neuer Job-Match von bullheads.de')
                ->htmlTemplate('emails/job_match.html.twig')
                ->textTemplate('emails/job_match.txt.twig')
                ->context([
                    'candidate' => $candidate,
                    'match' => $match,
                    'standardText' => 'Bitte prüfen Sie folgenden Job und geben Sie uns einen Hinweis, ob der Job für Sie interessant ist. Wir nehmen dann Kontakt zum Unternehmen auf.',
                    'jobDescriptionHtml' => $jobDescriptionHtml,
                    'brandColor' => '#073b6f',
                ]);

            try {
                $this->mailer->send($email);
                $match->setSentAt(new \DateTimeImmutable());
                $this->entityManager->persist($match);
                $results['success']++;
            } catch (\Throwable $e) {
                $results['errors'][] = sprintf('Match ID %d: %s', $match->getId(), $e->getMessage());
            }
        }

        $this->entityManager->flush();

        return $results;
    }
}
