<?php

namespace App\Controller\Admin;

use App\Repository\CommandeRepository;
use App\Repository\DonationRepository;
use App\Repository\EvenementRepository;
use App\Repository\ForumRepository;
use App\Repository\ProduitRepository;
use App\Repository\ReservationRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class StatsController extends AbstractController
{
    #[Route('/admin/statistiques', name: 'admin_stats')]
    #[IsGranted('ROLE_ADMIN')]
    public function index(
        ReservationRepository $reservationRepository,
        UserRepository $userRepository,
        EvenementRepository $evenementRepository,
        CommandeRepository $commandeRepository,
        DonationRepository $donationRepository,
        ProduitRepository $produitRepository,
        ForumRepository $forumRepository,
    ): Response {
        $reservationsByStatus = $reservationRepository->countByStatus();
        $totalReservations = $reservationRepository->count([]);
        $reservationsThisMonth = $reservationRepository->countThisMonth();
        $monthlyReservations = $reservationRepository->getMonthlyReservations(6);

        $usersByRole = $userRepository->getUsersByRole();
        $totalUsers = $userRepository->count([]);
        $newUsersThisMonth = $userRepository->countNewThisMonth();
        $monthlyRegistrations = $userRepository->getMonthlyRegistrations(6);

        $eventStats = $evenementRepository->getStatsOverview();
        $topEvents = $evenementRepository->getTopEvents(5);
        $monthlyEvents = $evenementRepository->getMonthlyEvents(6);

        $totalOrders = $commandeRepository->count([]);
        $totalDonations = $donationRepository->count([]);
        $totalProducts = $produitRepository->count([]);
        $totalForumPosts = $forumRepository->count([]);

        return $this->render('admin/stats.html.twig', [
            'totalReservations' => $totalReservations,
            'reservationsThisMonth' => $reservationsThisMonth,
            'reservationsByStatus' => $reservationsByStatus,
            'monthlyReservations' => $monthlyReservations,
            'totalUsers' => $totalUsers,
            'newUsersThisMonth' => $newUsersThisMonth,
            'usersByRole' => $usersByRole,
            'monthlyRegistrations' => $monthlyRegistrations,
            'eventStats' => $eventStats,
            'topEvents' => $topEvents,
            'monthlyEvents' => $monthlyEvents,
            'totalOrders' => $totalOrders,
            'totalDonations' => $totalDonations,
            'totalProducts' => $totalProducts,
            'totalForumPosts' => $totalForumPosts,
        ]);
    }
}
