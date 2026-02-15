<?php

namespace App\Controller;

use App\Repository\ForumRepository;
use App\Repository\EvenementRepository;
use App\Repository\ProduitRepository;
use App\Repository\DonationRepository;
use App\Repository\UserRepository;
use App\Repository\CommandeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class AdminDashboardController extends AbstractController
{
    #[Route('/admin/dashboard', name: 'admin_dashboard')]
    #[IsGranted('ROLE_ADMIN')]
    public function index(
        ForumRepository $forumRepository,
        EvenementRepository $evenementRepository,
        ProduitRepository $produitRepository,
        DonationRepository $donationRepository,
        UserRepository $userRepository,
        CommandeRepository $commandeRepository
    ): Response {
        // Statistiques générales
        $stats = [
            'users' => $userRepository->count([]),
            'forums' => $forumRepository->count([]),
            'events' => $evenementRepository->count([]),
            'products' => $produitRepository->count([]),
            'donations' => $donationRepository->count([]),
            'orders' => $commandeRepository->count([]),
        ];

        // Derniers forums
        $latestForums = $forumRepository->findBy([], ['dateCreation' => 'DESC'], 5);
        
        // Derniers événements
        $latestEvents = $evenementRepository->findBy([], ['createdAt' => 'DESC'], 5);
        
        // Derniers produits
        $latestProducts = $produitRepository->findBy([], ['id' => 'DESC'], 5);
        
        // Derniers dons
        $latestDonations = $donationRepository->findBy([], ['dateDon' => 'DESC'], 5);

        // Dernières commandes
        $latestOrders = $commandeRepository->findBy([], ['id' => 'DESC'], 5);

        return $this->render('back/admin/dashboard.html.twig', [
            'stats' => $stats,
            'latestForums' => $latestForums,
            'latestEvents' => $latestEvents,
            'latestProducts' => $latestProducts,
            'latestDonations' => $latestDonations,
            'latestOrders' => $latestOrders,
        ]);
    }
}
