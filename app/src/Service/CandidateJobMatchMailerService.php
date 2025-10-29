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

    public function sendMatch(Candidate $candidate, CandidateJobMatch $match, string $mailText): array
    {
        $results = [
            'success' => false,
            'error' => null,
        ];

        $candidateEmail = $candidate->getEmail();
        if (null === $candidateEmail) {
            $results['error'] = 'Keine E-Mail-Adresse hinterlegt.';
            return $results;
        }

        if (null !== $match->getSentAt()) {
            $results['error'] = 'Diese Mail wurde bereits versendet.';
            return $results;
        }

        $fromEmail = $candidate->getConsultant()?->getEmail() ?? 'info@bullheads.de';

        $email = (new TemplatedEmail())
            ->from($fromEmail)
            ->to($candidateEmail)
            ->bcc($fromEmail)
            ->subject('Ihr neuer Job-Match von bullheads.de')
            ->htmlTemplate('emails/job_match.html.twig')
            ->textTemplate('emails/job_match.txt.twig')
            ->context([
                'mailText' => $mailText,
                'plainText' => $this->htmlToPlaintext($mailText),
                'brandColor' => '#073b6f',
            ]);

        try {
            $this->mailer->send($email);
            $match->setSentAt(new \DateTimeImmutable());
            $this->entityManager->persist($match);
            $this->entityManager->flush();

            $results['success'] = true;
        } catch (\Throwable $e) {
            $results['error'] = $e->getMessage();
        }

        return $results;
    }

    private function htmlToPlaintext(string $html): string
    {
        $plainText = strip_tags($html);

        $plainText = implode("\n", array_map('trim', explode("\n", $plainText)));
        $plainText = preg_replace("/\n{3,}/", "\n\n", $plainText);

        return $plainText;
    }
}
