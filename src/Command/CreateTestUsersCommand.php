<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:create-test-users',
    description: 'Create test users (participant and artist)',
)]
class CreateTestUsersCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Create participant
        $participant = new User();
        $participant->setEmail('participant@test.com');
        $participant->setNom('Dupont');
        $participant->setPrenom('Jean');
        $participant->setRoles(['ROLE_PARTICIPANT']);
        $participant->setTelephone('12345678');
        
        $hashedPassword = $this->passwordHasher->hashPassword($participant, 'participant123');
        $participant->setPassword($hashedPassword);
        
        $this->entityManager->persist($participant);

        // Create artist
        $artiste = new User();
        $artiste->setEmail('artiste@test.com');
        $artiste->setNom('Martin');
        $artiste->setPrenom('Sophie');
        $artiste->setRoles(['ROLE_ARTISTE']);
        $artiste->setTelephone('87654321');
        
        $hashedPassword = $this->passwordHasher->hashPassword($artiste, 'artiste123');
        $artiste->setPassword($hashedPassword);
        
        $this->entityManager->persist($artiste);

        $this->entityManager->flush();

        $io->success('Utilisateurs de test créés avec succès !');
        $io->text('Participant: participant@test.com / participant123');
        $io->text('Artiste: artiste@test.com / artiste123');
        $io->text('Admin: admin@artconnect.local / password123');

        return Command::SUCCESS;
    }
}
