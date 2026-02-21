<?php

namespace App\Controller\Event;

use App\Entity\Evenement;
use App\Service\EmailService;
use App\Service\StripeService;
use App\Entity\Reservation;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/reservation')]
class ReservationController extends AbstractController
{
    public function __construct(
        private StripeService $stripeService,
        private EmailService $emailService,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    #[Route('/payment-success', name: 'app_reservation_payment_success', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function paymentSuccess(): Response
    {
        $this->addFlash('success', 'Votre paiement a été enregistré. Votre réservation est confirmée. Un email récapitulatif vous a été envoyé.');
        return $this->redirectToRoute('app_reservation_my');
    }

    #[Route('/my-reservations', name: 'app_reservation_my', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function myReservations(Request $request, ReservationRepository $reservationRepository): Response
    {
        $isAdmin = $this->isGranted('ROLE_ADMIN');

        $filterInput = [
            'q' => trim((string) $request->query->get('q', '')),
            'status' => trim((string) $request->query->get('status', '')),
            'date_start' => (string) $request->query->get('date_start', ''),
            'date_end' => (string) $request->query->get('date_end', ''),
            'sort' => (string) $request->query->get('sort', 'date_desc'),
        ];

        $filters = [
            'q' => $filterInput['q'],
            'status' => $filterInput['status'],
            'date_start' => $this->parseDate($filterInput['date_start']),
            'date_end' => $this->parseDate($filterInput['date_end'], true),
            'sort' => $filterInput['sort'],
        ];

        $page = max(1, (int) $request->query->get('page', 1));
        $perPage = 10;
        $paginator = $reservationRepository->searchAndSort($filters, $page, $perPage, $this->getUser(), $isAdmin);
        $total = count($paginator);
        $totalPages = max(1, (int) ceil($total / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
            $paginator = $reservationRepository->searchAndSort($filters, $page, $perPage, $this->getUser(), $isAdmin);
        }

        return $this->render('reservation/my_reservations.html.twig', [
            'reservations' => iterator_to_array($paginator, false),
            'isAdmin' => $isAdmin,
            'pageTitle' => $isAdmin ? 'Gestion des Réservations' : 'Mes Réservations',
            'filter_input' => $filterInput,
            'page' => $page,
            'total_pages' => $totalPages,
            'total' => $total,
        ]);
    }

    #[Route('/{id}/book', name: 'app_reservation_book', methods: ['POST'])]
    #[IsGranted('ROLE_PARTICIPANT')]
    public function book(Evenement $evenement, EntityManagerInterface $entityManager, ReservationRepository $reservationRepository): Response
    {
        $user = $this->getUser();

        $existingReservation = $reservationRepository->findOneBy([
            'evenement' => $evenement,
            'participant' => $user
        ]);

        if ($existingReservation) {
            $this->addFlash('warning', 'Vous avez déjà réservé une place pour cet événement.');
            return $this->redirectToRoute('app_evenement_show', ['id' => $evenement->getId()]);
        }

        $now = new \DateTime();
        if ($evenement->getDateDebut() < $now) {
            $this->addFlash('danger', 'Impossible de réserver un événement déjà passé.');
            return $this->redirectToRoute('app_evenement_show', ['id' => $evenement->getId()]);
        }

        if ($evenement->getReservations()->count() >= $evenement->getNbPlaces()) {
            $this->addFlash('danger', 'Désolé, cet événement est complet.');
            return $this->redirectToRoute('app_evenement_show', ['id' => $evenement->getId()]);
        }

        $reservation = new Reservation();
        $reservation->setEvenement($evenement);
        $reservation->setParticipant($user);

        $prix = $evenement->getPrix();
        $isPaid = $prix !== null && $prix > 0;

        if ($isPaid) {
            $reservation->setStatus(Reservation::STATUS_PENDING);
            $entityManager->persist($reservation);
            $entityManager->flush();

            $successUrl = $this->urlGenerator->generate('app_reservation_payment_success', [], UrlGeneratorInterface::ABSOLUTE_URL);
            $cancelUrl = $this->urlGenerator->generate('app_evenement_show', ['id' => $evenement->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
            $checkoutUrl = $this->stripeService->createCheckoutSessionForReservation($reservation, $successUrl, $cancelUrl);
            return $this->redirect($checkoutUrl);
        }

        $reservation->setStatus(Reservation::STATUS_CONFIRMED);
        $entityManager->persist($reservation);
        $entityManager->flush();
        $this->emailService->sendReservationConfirmationDetails($reservation);
        $this->addFlash('success', 'Votre réservation a été confirmée ! Un email récapitulatif vous a été envoyé.');
        return $this->redirectToRoute('app_reservation_my');
    }

    #[Route('/{id}/book-seat', name: 'app_reservation_book_seat', methods: ['POST'])]
    #[IsGranted('ROLE_PARTICIPANT')]
    public function bookSeat(Request $request, Evenement $evenement, EntityManagerInterface $entityManager, ReservationRepository $reservationRepository): Response
    {
        $user = $this->getUser();
        $seat = trim((string) $request->request->get('seat', ''));

        if (!$seat || !$evenement->getLayoutType()) {
            $this->addFlash('danger', 'Veuillez sélectionner une place sur le plan.');
            return $this->redirectToRoute('app_evenement_show', ['id' => $evenement->getId()]);
        }

        $existing = $reservationRepository->findOneBy([
            'evenement' => $evenement,
            'participant' => $user
        ]);
        if ($existing) {
            $this->addFlash('warning', 'Vous avez déjà réservé une place pour cet événement.');
            return $this->redirectToRoute('app_evenement_show', ['id' => $evenement->getId()]);
        }

        $seatTaken = $reservationRepository->findOneBy([
            'evenement' => $evenement,
            'seatLabel' => $seat
        ]);
        if ($seatTaken) {
            $this->addFlash('danger', 'Cette place est déjà prise. Veuillez en choisir une autre.');
            return $this->redirectToRoute('app_evenement_show', ['id' => $evenement->getId()]);
        }

        $now = new \DateTime();
        if ($evenement->getDateDebut() < $now) {
            $this->addFlash('danger', 'Impossible de réserver un événement déjà passé.');
            return $this->redirectToRoute('app_evenement_show', ['id' => $evenement->getId()]);
        }

        if ($evenement->getReservations()->count() >= $evenement->getNbPlaces()) {
            $this->addFlash('danger', 'Désolé, cet événement est complet.');
            return $this->redirectToRoute('app_evenement_show', ['id' => $evenement->getId()]);
        }

        $reservation = new Reservation();
        $reservation->setEvenement($evenement);
        $reservation->setParticipant($user);
        $reservation->setSeatLabel($seat);

        $prix = $evenement->getPrix();
        $isPaid = $prix !== null && $prix > 0;

        if ($isPaid) {
            $reservation->setStatus(Reservation::STATUS_PENDING);
            $entityManager->persist($reservation);
            $entityManager->flush();

            $successUrl = $this->urlGenerator->generate('app_reservation_payment_success', [], UrlGeneratorInterface::ABSOLUTE_URL);
            $cancelUrl = $this->urlGenerator->generate('app_evenement_show', ['id' => $evenement->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
            $checkoutUrl = $this->stripeService->createCheckoutSessionForReservation($reservation, $successUrl, $cancelUrl);
            return $this->redirect($checkoutUrl);
        }

        $reservation->setStatus(Reservation::STATUS_CONFIRMED);
        $entityManager->persist($reservation);
        $entityManager->flush();
        $this->emailService->sendReservationConfirmationDetails($reservation);
        $this->addFlash('success', sprintf('Votre place %s a été réservée avec succès ! Un email récapitulatif vous a été envoyé.', $seat));
        return $this->redirectToRoute('app_evenement_show', ['id' => $evenement->getId()]);
    }

    #[Route('/{id}/cancel', name: 'app_reservation_cancel', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function cancel(Reservation $reservation, EntityManagerInterface $entityManager): Response
    {
        $isAdmin = $this->isGranted('ROLE_ADMIN');
        $currentUser = $this->getUser();
        
        if (!$isAdmin && $reservation->getParticipant() !== $currentUser) {
            throw $this->createAccessDeniedException('Vous n\'avez pas les droits nécessaires pour effectuer cette action.');
        }

        $eventTitle = $reservation->getEvenement()->getTitre();
        
        if ($reservation->getEvenement()->getDateDebut() < new \DateTime() && !$isAdmin) {
            $this->addFlash('error', 'Impossible d\'annuler une réservation pour un événement déjà passé.');
            return $this->redirectToRoute('app_reservation_my');
        }

        $entityManager->remove($reservation);
        $entityManager->flush();

        if ($isAdmin && $reservation->getParticipant() !== $currentUser) {
            $this->addFlash('success', sprintf('La réservation pour l\'événement "%s" a été supprimée avec succès.', $eventTitle));
        } else {
            $this->addFlash('success', 'Votre réservation a été annulée avec succès.');
        }

        return $this->redirectToRoute('app_reservation_my');
    }

    private function parseDate(?string $value, bool $endOfDay = false): ?\DateTimeImmutable
    {
        if ($value === null || $value === '') {
            return null;
        }

        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $value);
        if ($date === false) {
            return null;
        }

        if ($endOfDay) {
            return $date->setTime(23, 59, 59);
        }

        return $date->setTime(0, 0, 0);
    }
}
