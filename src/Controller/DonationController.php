<?php

namespace App\Controller;

use App\Entity\Donation;
use App\Form\DonationType;
use App\Repository\DonationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/donation')]
class DonationController extends AbstractController
{
    /**
     * ADMIN VIEW: Full List with Dynamic Sorting
     */
    #[Route('/admin', name: 'app_donation_index', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function index(Request $request, DonationRepository $donationRepository): Response
    {
        // Get sorting preferences from URL, default to 'dateDon' DESC
        $sort = $request->query->get('sort', 'dateDon');
        $direction = strtoupper($request->query->get('direction', 'DESC'));

        // Validate sort field to prevent SQL errors
        $allowedSorts = ['dateDon', 'description', 'type', 'donateur'];
        if (!in_array($sort, $allowedSorts)) { $sort = 'dateDon'; }
        if (!in_array($direction, ['ASC', 'DESC'])) { $direction = 'DESC'; }

        return $this->render('donation/index.html.twig', [
            'donations' => $donationRepository->findAllSorted($sort, $direction),
            'currentSort' => $sort,
            'currentDirection' => $direction,
        ]);
    }

    /**
     * USER VIEW: Their own donations
     */
    #[Route('/my-donations', name: 'app_donation_my', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function myDonations(DonationRepository $donationRepository): Response
    {
        return $this->render('donation/my_donations.html.twig', [
            'donations' => $donationRepository->findBy(
                ['donateur' => $this->getUser()],
                ['dateDon' => 'DESC']
            ),
        ]);
    }

    /**
     * CREATE
     */
    #[Route('/new', name: 'app_donation_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $donation = new Donation();
        $form = $this->createForm(DonationType::class, $donation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $donation->setDonateur($this->getUser());
            $entityManager->persist($donation);
            $entityManager->flush();

            $this->addFlash('success', 'Merci pour votre don !');
            return $this->redirectToRoute('app_donation_my');
        }

        return $this->render('donation/new.html.twig', [
            'donation' => $donation,
            'form' => $form,
        ]);
    }

    /**
     * EDIT (Admin Only)
     */
    #[Route('/admin/{id}/edit', name: 'app_donation_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(Request $request, Donation $donation, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(DonationType::class, $donation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Donation modifiée avec succès.');
            return $this->redirectToRoute('app_donation_index');
        }

        return $this->render('donation/edit.html.twig', [
            'donation' => $donation,
            'form' => $form,
        ]);
    }

    /**
     * DELETE (Admin Only)
     */
    #[Route('/admin/{id}/delete', name: 'app_donation_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, Donation $donation, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$donation->getId(), $request->request->get('_token'))) {
            $entityManager->remove($donation);
            $entityManager->flush();
            $this->addFlash('success', 'Donation supprimée.');
        }

        return $this->redirectToRoute('app_donation_index');
    }
}
