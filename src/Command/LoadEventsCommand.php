<?php

namespace App\Command;

use App\Entity\Evenement;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:load-events',
    description: 'Load sample events for children with special needs',
)]
class LoadEventsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        // Récupérer un administrateur comme organisateur
        $organisateur = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'admin@artconnect.local']);
        
        if (!$organisateur) {
            $io->error('Aucun administrateur trouvé avec l\'email admin@artconnect.local');
            return Command::FAILURE;
        }

        $events = [
            [
                'titre' => 'Atelier Peinture Sensorielle',
                'description' => 'Découverte des couleurs et des textures à travers la peinture. Adapté aux enfants avec autisme ou troubles sensoriels.',
                'lieu' => 'Centre Culturel de Tunis, Avenue Habib Bourguiba, 1001 Tunis',
                'ageMin' => 4,
                'ageMax' => 10,
                'nbPlaces' => 8,
                'prix' => 15.00,
                'dateDebut' => '+1 week',
                'duree' => '2 hours',
                'image' => 'art-kids-1.png'
            ],
            [
                'titre' => 'Danse Adaptée en Musique',
                'description' => 'Expression corporelle et rythmique pour enfants en fauteuil roulant ou avec mobilité réduite.',
                'lieu' => 'Centre National de la Danse, La Marsa, 2078 Tunisie',
                'ageMin' => 5,
                'ageMax' => 12,
                'nbPlaces' => 10,
                'prix' => 12.00,
                'dateDebut' => '+2 weeks',
                'duree' => '1.5 hours',
                'image' => 'art-kids-2.png'
            ],
            [
                'titre' => 'Théâtre des Sens',
                'description' => 'Atelier de théâtre utilisant la langue des signes et des éléments sensoriels pour enfants sourds ou malentendants.',
                'lieu' => 'Théâtre Municipal de Tunis, Avenue de Paris, 1000 Tunis',
                'ageMin' => 6,
                'ageMax' => 14,
                'nbPlaces' => 12,
                'prix' => 18.00,
                'dateDebut' => '+3 weeks',
                'duree' => '2 hours',
                'image' => 'art-kids-3.png'
            ],
            [
                'titre' => 'Musique et Émotions',
                'description' => 'Découverte des émotions à travers les sons et la musique pour enfants avec troubles du spectre autistique.',
                'lieu' => 'Conservatoire National de Musique, Rue du Pacha, 1002 Tunis',
                'ageMin' => 4,
                'ageMax' => 10,
                'nbPlaces' => 8,
                'prix' => 14.00,
                'dateDebut' => '+4 weeks',
                'duree' => '1.5 hours',
                'image' => 'art-kids-1.png'
            ],
            [
                'titre' => 'Sculpture Tactile',
                'description' => 'Création artistique avec différentes matières pour enfants déficients visuels.',
                'lieu' => 'Centre des Arts Vivants, La Medina, 1008 Tunis',
                'ageMin' => 6,
                'ageMax' => 12,
                'nbPlaces' => 6,
                'prix' => 16.00,
                'dateDebut' => '+5 weeks',
                'duree' => '2 hours',
                'image' => 'art-kids-2.png'
            ],
            [
                'titre' => 'Expression Corporelle Libre',
                'description' => 'Mouvement et expression sans jugement pour enfants avec troubles de l\'attention ou hyperactivité.',
                'lieu' => 'Espace Culturel El Teatro, Avenue de la Liberté, 1002 Tunis',
                'ageMin' => 5,
                'ageMax' => 12,
                'nbPlaces' => 10,
                'prix' => 13.00,
                'dateDebut' => '+6 weeks',
                'duree' => '1.5 hours',
                'image' => 'art-kids-3.png'
            ],
            [
                'titre' => 'Peinture sur Toile Géante',
                'description' => 'Atelier collaboratif de peinture sur grande surface pour favoriser la motricité fine et la coopération.',
                'lieu' => 'Galerie d\'Art A. Gorgi, Sidi Bou Said, 2026 Tunisie',
                'ageMin' => 4,
                'ageMax' => 10,
                'nbPlaces' => 10,
                'prix' => 15.00,
                'dateDebut' => '+7 weeks',
                'duree' => '2 hours',
                'image' => 'art-kids-1.png'
            ],
            [
                'titre' => 'Création de Marionnettes',
                'description' => 'Fabrication et manipulation de marionnettes pour enfants avec troubles de la communication.',
                'lieu' => 'Théâtre des Jeunes, Cité de la Culture, 1001 Tunis',
                'ageMin' => 5,
                'ageMax' => 12,
                'nbPlaces' => 8,
                'prix' => 14.00,
                'dateDebut' => '+8 weeks',
                'duree' => '2 hours',
                'image' => 'art-kids-2.png'
            ],
            [
                'titre' => 'Atelier Percussions',
                'description' => 'Découverte des rythmes et des percussions pour enfants avec troubles du développement.',
                'lieu' => 'Institut Supérieur de Musique, Rue du Lac de Constance, 1053 Tunis',
                'ageMin' => 6,
                'ageMax' => 14,
                'nbPlaces' => 10,
                'prix' => 16.00,
                'dateDebut' => '+9 weeks',
                'duree' => '1.5 hours',
                'image' => 'art-kids-3.png'
            ],
            [
                'titre' => 'Land Art Naturel',
                'description' => 'Création artistique éphémère avec des éléments naturels pour une expérience sensorielle complète.',
                'lieu' => 'Parc du Belvédère, Avenue Taieb Mhiri, 1002 Tunis',
                'ageMin' => 4,
                'ageMax' => 12,
                'nbPlaces' => 12,
                'prix' => 10.00,
                'dateDebut' => '+10 weeks',
                'duree' => '2 hours',
                'image' => 'art-kids-1.png'
            ]
        ];

        foreach ($events as $eventData) {
            $event = new Evenement();
            $event->setTitre($eventData['titre']);
            $event->setDescription($eventData['description']);
            $event->setLieu($eventData['lieu']);
            $event->setAgeMin($eventData['ageMin']);
            $event->setAgeMax($eventData['ageMax']);
            $event->setNbPlaces($eventData['nbPlaces']);
            $event->setPrix($eventData['prix']);
            $event->setOrganisateur($organisateur);
            $event->setImage($eventData['image']);
            
            $dateDebut = new \DateTime($eventData['dateDebut']);
            $event->setDateDebut($dateDebut);
            
            $dateFin = clone $dateDebut;
            // Convertir la durée en secondes (ex: '2 hours' -> 7200 secondes)
            $seconds = strtotime($eventData['duree']) - time();
            $dateFin->modify("+{$seconds} seconds");
            $event->setDateFin($dateFin);
            
            $this->entityManager->persist($event);
        }

        $this->entityManager->flush();

        $io->success('10 événements ont été ajoutés avec succès !');
        return Command::SUCCESS;
    }
}
