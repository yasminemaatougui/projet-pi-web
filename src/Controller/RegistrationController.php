<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager,
        EmailService $emailService,
        UserRepository $userRepository
    ): Response {
        // Redirect if already logged in
        if ($this->getUser()) {
            return $this->redirectToRoute('home');
        }

        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $email = $form->get('email')->getData();
            $existing = $userRepository->findOneBy(['email' => $email]);

            // If user exists and is pending verification, resend email
            if ($existing && $existing->getStatus() === User::STATUS_EMAIL_PENDING) {
                $existing->generateEmailVerificationToken();
                $entityManager->flush();

                try {
                    $emailService->sendEmailVerification($existing);
                    $this->addFlash('success', 'Un nouveau lien de vérification a été envoyé à ' . $email);
                } catch (\Exception $e) {
                    $this->addFlash('warning', 'Erreur d\'envoi : ' . $e->getMessage());
                }

                return $this->redirectToRoute('app_registration_confirmation', ['email' => $email]);
            }

            // If user already exists with different status
            if ($existing) {
                $this->addFlash('danger', 'Un compte avec cet email existe déjà.');
                return $this->render('registration/register.html.twig', [
                    'registrationForm' => $form,
                ]);
            }

            // Get the selected role from the form
            $selectedRole = $form->get('role')->getData();
            
            // Set the user's role
            $user->setRoles([$selectedRole]);
            
            // Set status to EMAIL_PENDING for all users (they need to verify email first)
            $user->setStatus(User::STATUS_EMAIL_PENDING);
            $user->generateEmailVerificationToken();
            
            // Encode the plain password
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            $entityManager->persist($user);
            $entityManager->flush();

            // Send verification email
            try {
                // Ensure token exists before sending
                if (!$user->getEmailVerificationToken()) {
                    $user->generateEmailVerificationToken();
                    $entityManager->flush();
                }
                
                $emailService->sendEmailVerification($user);
                $this->addFlash('success', 'Un email de vérification a été envoyé à ' . $email);
            } catch (\Exception $e) {
                // Log the full error for debugging
                error_log('Email sending error: ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
                $this->addFlash('warning', 'Erreur d\'envoi : ' . $e->getMessage());
            }

            return $this->redirectToRoute('app_registration_confirmation', ['email' => $email]);
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }

    #[Route('/register/confirmation', name: 'app_registration_confirmation')]
    public function confirmation(Request $request): Response
    {
        return $this->render('registration/confirmation.html.twig', [
            'email' => $request->query->get('email', ''),
        ]);
    }
}
