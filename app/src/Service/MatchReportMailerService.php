<?php

namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class MatchReportMailerService
{
    public function __construct(private MailerInterface $mailer) {}

    public function sendReport(string $csvPath): void
    {
        $email = (new Email())
            ->from('noreply@domain.de')
            ->to('frank@domain.de')
            ->subject('Aktueller Job Matching Report')
            ->text('Hallo Frank, anbei der aktuelle Matching-Report als CSV.')
            ->attachFromPath($csvPath);

        $this->mailer->send($email);
    }
}
