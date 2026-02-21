<?php

namespace App\Service;

use App\Entity\Reservation;
use Stripe\StripeClient;

class StripeService
{
    /** Stripe currency (must be supported by your Stripe account, e.g. eur, usd). */
    private const CURRENCY = 'eur';
    /** EUR smallest unit: 1 EUR = 100 cents. */
    private const AMOUNT_MULTIPLIER = 100;

    public function __construct(
        private string $stripeSecretKey,
    ) {
    }

    /**
     * Creates a Stripe Checkout Session for a paid reservation.
     * Returns the session URL to redirect the user to.
     */
    public function createCheckoutSessionForReservation(
        Reservation $reservation,
        string $successUrl,
        string $cancelUrl,
    ): string {
        $stripe = new StripeClient($this->stripeSecretKey);
        $evenement = $reservation->getEvenement();
        $prix = $evenement->getPrix();
        if ($prix === null || $prix <= 0) {
            throw new \InvalidArgumentException('Event must have a positive price for Stripe Checkout.');
        }

        $amount = (int) round($prix * self::AMOUNT_MULTIPLIER);
        if ($amount < 1) {
            $amount = 1;
        }

        $session = $stripe->checkout->sessions->create([
            'payment_method_types' => ['card'],
            'line_items' => [
                [
                    'price_data' => [
                        'currency' => self::CURRENCY,
                        'product_data' => [
                            'name' => $evenement->getTitre(),
                            'description' => sprintf(
                                'Réservation pour %s — %s',
                                $evenement->getTitre(),
                                $evenement->getDateDebut() ? $evenement->getDateDebut()->format('d/m/Y H:i') : ''
                            ),
                            'metadata' => [
                                'event_id' => (string) $evenement->getId(),
                                'reservation_id' => (string) $reservation->getId(),
                            ],
                        ],
                        'unit_amount' => $amount,
                    ],
                    'quantity' => 1,
                ],
            ],
            'mode' => 'payment',
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'metadata' => [
                'reservation_id' => (string) $reservation->getId(),
                'event_id' => (string) $evenement->getId(),
                'user_id' => (string) $reservation->getParticipant()->getId(),
                'seat_label' => $reservation->getSeatLabel() ?? '',
            ],
        ]);

        return $session->url;
    }
}
