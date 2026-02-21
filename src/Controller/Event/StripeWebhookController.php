<?php

namespace App\Controller\Event;

use App\Entity\Reservation;
use App\Repository\ReservationRepository;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/stripe-webhook', name: 'app_stripe_webhook_', methods: ['POST'])]
class StripeWebhookController extends AbstractController
{
    private string $stripeWebhookSecret;

    public function __construct(
        ?string $stripeWebhookSecret,
        private ReservationRepository $reservationRepository,
        private EntityManagerInterface $entityManager,
        private EmailService $emailService,
        private LoggerInterface $logger,
    ) {
        $this->stripeWebhookSecret = $stripeWebhookSecret ?? '';
    }

    #[Route('', name: 'handle', methods: ['POST'])]
    public function handle(Request $request): Response
    {
        $payload = $request->getContent();
        $sigHeader = $request->headers->get('Stripe-Signature', '');

        $this->logger->info('Stripe webhook received', [
            'content_length' => \strlen($payload),
            'has_signature' => $sigHeader !== '',
            'webhook_secret_configured' => $this->stripeWebhookSecret !== '',
        ]);

        if ($payload === '') {
            $this->logger->warning('Stripe webhook: empty payload (raw body may have been consumed elsewhere)');
            return new Response('Empty payload', Response::HTTP_BAD_REQUEST);
        }

        if ($sigHeader === '') {
            $this->logger->warning('Stripe webhook: missing Stripe-Signature header');
            return new Response('Missing signature', Response::HTTP_BAD_REQUEST);
        }

        if ($this->stripeWebhookSecret === '') {
            $this->logger->error('Stripe webhook: STRIPE_WEBHOOK_SECRET is not set');
            return new Response('Webhook not configured', Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $this->stripeWebhookSecret);
        } catch (SignatureVerificationException $e) {
            $this->logger->warning('Stripe webhook: invalid signature', ['message' => $e->getMessage()]);
            return new Response('Invalid signature', Response::HTTP_BAD_REQUEST);
        } catch (\UnexpectedValueException $e) {
            $this->logger->warning('Stripe webhook: invalid payload', ['message' => $e->getMessage()]);
            return new Response('Invalid payload', Response::HTTP_BAD_REQUEST);
        }

        $this->logger->info('Stripe webhook: event parsed', [
            'event_id' => $event->id ?? null,
            'event_type' => $event->type ?? null,
        ]);

        if ($event->type === 'checkout.session.completed') {
            $this->handleCheckoutSessionCompleted($event->data->object);
        } else {
            $this->logger->info('Stripe webhook: event type ignored', ['event_type' => $event->type]);
        }

        return new Response('', Response::HTTP_OK);
    }

    private function handleCheckoutSessionCompleted(\Stripe\Checkout\Session $session): void
    {
        $reservationId = $session->metadata->reservation_id ?? null;

        $this->logger->info('Stripe webhook: checkout.session.completed', [
            'session_id' => $session->id ?? null,
            'reservation_id_metadata' => $reservationId,
            'amount_total' => $session->amount_total ?? null,
        ]);

        if (!$reservationId) {
            $this->logger->warning('Stripe webhook: no reservation_id in session metadata');
            return;
        }

        $reservation = $this->reservationRepository->find((int) $reservationId);
        if (!$reservation instanceof Reservation) {
            $this->logger->warning('Stripe webhook: reservation not found', ['reservation_id' => $reservationId]);
            return;
        }

        if ($reservation->getStatus() !== Reservation::STATUS_PENDING) {
            $this->logger->info('Stripe webhook: reservation already processed (status)', [
                'reservation_id' => $reservationId,
                'current_status' => $reservation->getStatus(),
            ]);
            return;
        }
        if ($reservation->getStripeCheckoutSessionId() !== null) {
            $this->logger->info('Stripe webhook: reservation already linked to a session (idempotency)', [
                'reservation_id' => $reservationId,
            ]);
            return;
        }

        $amountTotal = $session->amount_total ?? 0;

        $reservation->setStatus(Reservation::STATUS_CONFIRMED);
        $reservation->setStripeCheckoutSessionId($session->id);
        $reservation->setAmountPaid((int) $amountTotal);

        $this->entityManager->flush();

        $this->logger->info('Stripe webhook: reservation confirmed and email sent', [
            'reservation_id' => $reservationId,
            'session_id' => $session->id,
        ]);

        $this->emailService->sendReservationConfirmationDetails($reservation);
    }
}
