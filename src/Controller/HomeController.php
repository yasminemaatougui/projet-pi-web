<?php

namespace App\Controller;

use App\Repository\EvenementRepository;
use App\Repository\ForumRepository;
use App\Repository\ProduitRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(
        EvenementRepository $evenementRepository,
        ProduitRepository $produitRepository,
        ForumRepository $forumRepository,
    ): Response {
        return $this->render('home/index.html.twig', [
            'user' => $this->getUser(),
            'latestEvents' => $evenementRepository->findBy([], ['dateDebut' => 'ASC'], 6),
            'latestProduits' => $produitRepository->findBy([], ['id' => 'DESC'], 4),
            'latestForums' => $forumRepository->findBy([], ['dateCreation' => 'DESC'], 3),
        ]);
    }
}
