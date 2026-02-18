<?php

namespace App\Controller\Admin;

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
    public function index(ReservationRepository $reservationRepository, UserRepository $userRepository): Response
    {
        // Vérification des rôles de l'utilisateur connecté
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour accéder à cette page.');
        }
        
        // Vérification des rôles
        if (!in_array('ROLE_ADMIN', $user->getRoles())) {
            throw $this->createAccessDeniedException('Accès refusé. Rôles de l\'utilisateur : ' . implode(', ', $user->getRoles()));
        }
        
        try {
            // Récupération des statistiques des réservations
            $reservationsByStatus = $reservationRepository->countByStatus();
            $totalReservations = $reservationRepository->count([]);
            $reservationsThisMonth = $reservationRepository->countThisMonth();
            
            // Récupération des statistiques des utilisateurs
            $usersByRole = $userRepository->getUsersByRole();
            $totalUsers = $userRepository->count([]);
            $newUsersThisMonth = $userRepository->countNewThisMonth();
            $monthlyRegistrations = $userRepository->getMonthlyRegistrations(12);
            
            // Vérification des données
            if (empty($usersByRole)) {
                throw new \RuntimeException('Aucune donnée utilisateur trouvée');
            }
        } catch (\Exception $e) {
            // En cas d'erreur, on utilise des valeurs par défaut
            $this->addFlash('error', 'Erreur lors de la récupération des statistiques : ' . $e->getMessage());
            
            // Valeurs par défaut pour éviter les erreurs
            $usersByRole = [
                ['role' => 'ROLE_ADMIN', 'count' => 0],
                ['role' => 'ROLE_ARTISTE', 'count' => 0],
                ['role' => 'ROLE_PARTICIPANT', 'count' => 0]
            ];
            $totalUsers = 0;
            $newUsersThisMonth = 0;
            $monthlyRegistrations = [];
            $totalReservations = 0;
            $reservationsThisMonth = 0;
            $reservationsByStatus = [];
        }

        return $this->render('admin/stats.html.twig', [
            'totalReservations' => $totalReservations,
            'reservationsThisMonth' => $reservationsThisMonth,
            'reservationsByStatus' => $reservationsByStatus,
            'totalUsers' => $totalUsers,
            'newUsersThisMonth' => $newUsersThisMonth,
            'usersByRole' => $usersByRole,
            'monthlyRegistrations' => $monthlyRegistrations,
        ]);
    }
}
