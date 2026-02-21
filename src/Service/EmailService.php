<?php

namespace App\Service;

use App\Entity\Reservation;
use App\Entity\User;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class EmailService
{
    public function __construct(
        private MailerInterface $mailer,
        private string $adminEmail,
        private UrlGeneratorInterface $urlGenerator,
        private int $emailVerificationTtlHours,
    ) {}

    public function sendEmailVerification(User $user): void
    {
        $token = $user->getEmailVerificationToken();
        
        if (!$token) {
            throw new \RuntimeException('Email verification token is missing. Please generate a token first.');
        }

        $url = $this->urlGenerator->generate('app_verify_email', [
            'token' => $token,
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $email = (new TemplatedEmail())
            ->from($this->adminEmail)
            ->to($user->getEmail())
            ->subject('Vérifiez votre adresse email — Art Connect')
            ->htmlTemplate('emails/email_verification.html.twig')
            ->context([
                'user' => $user,
                'verificationUrl' => $url,
                'ttlHours' => $this->emailVerificationTtlHours,
            ]);

        $this->mailer->send($email);
    }

    public function sendAccountApproved(User $user): void
    {
        $email = (new TemplatedEmail())
            ->from($this->adminEmail)
            ->to($user->getEmail())
            ->subject('Votre compte a été approuvé — Art Connect')
            ->htmlTemplate('emails/account_approved.html.twig')
            ->context(['user' => $user]);

        $this->mailer->send($email);
    }

    public function sendAccountRejected(User $user): void
    {
        $email = (new TemplatedEmail())
            ->from($this->adminEmail)
            ->to($user->getEmail())
            ->subject('Mise à jour de votre compte — Art Connect')
            ->htmlTemplate('emails/account_rejected.html.twig')
            ->context(['user' => $user]);

        $this->mailer->send($email);
    }

    public function sendReservationConfirmation($participantEmail, $participantName, $eventName, $eventDate): void
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

    /**
     * Sends a confirmation email with full reservation and event details (e.g. after Stripe payment).
     */
    public function sendReservationConfirmationDetails(Reservation $reservation): void
    {
        $participant = $reservation->getParticipant();
        $evenement = $reservation->getEvenement();
        $participantName = trim($participant->getPrenom() . ' ' . $participant->getNom());
        $eventDate = $evenement->getDateDebut() ? $evenement->getDateDebut()->format('d/m/Y à H:i') : '';
        $lieu = $evenement->getLieu() ?? '';
        $seatLabel = $reservation->getSeatLabel();
        $amountPaid = $reservation->getAmountPaid();
        // Stripe amount is in cents (EUR)
        $priceFormatted = $amountPaid !== null
            ? number_format($amountPaid / 100, 2, ',', ' ') . ' EUR'
            : ($evenement->getPrix() !== null ? number_format($evenement->getPrix(), 2, ',', ' ') . ' EUR' : 'Gratuit');
        $reservationDate = $reservation->getDateReservation() ? $reservation->getDateReservation()->format('d/m/Y à H:i') : '';

        $email = (new TemplatedEmail())
            ->from($this->adminEmail)
            ->to($participant->getEmail())
            ->subject('Confirmation de votre réservation — ' . $evenement->getTitre())
            ->htmlTemplate('emails/reservation_confirmation.html.twig')
            ->context([
                'name' => $participantName,
                'event' => $evenement->getTitre(),
                'date' => $eventDate,
                'lieu' => $lieu,
                'seat_label' => $seatLabel,
                'price' => $priceFormatted,
                'reservation_date' => $reservationDate,
                'description' => $evenement->getDescription(),
            ]);

        $this->mailer->send($email);
    }
}
