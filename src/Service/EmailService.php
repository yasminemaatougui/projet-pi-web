<?php

namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;

class EmailService
{
    private MailerInterface $mailer;
    private string $adminEmail;

    public function __construct(MailerInterface $mailer, string $adminEmail)
    {
        $this->mailer = $mailer;
        $this->adminEmail = $adminEmail;
    }

    public function sendReservationConfirmation($participantEmail, $participantName, $eventName, $eventDate)
    {
        $email = (new TemplatedEmail())
            ->from($this->adminEmail)
            ->to($participantEmail)
            ->subject('Confirmation de votre réservation')
            ->htmlTemplate('emails/reservation_confirmation.html.twig')
            ->context([
                'name' => $participantName,
                'event' => $eventName,
                'date' => $eventDate,
            ]);

        $this->mailer->send($email);
    }
}
