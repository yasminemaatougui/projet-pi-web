<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260208141123 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE commande (id INT AUTO_INCREMENT NOT NULL, date_commande DATETIME NOT NULL, statut VARCHAR(255) NOT NULL, total DOUBLE PRECISION NOT NULL, user_id INT NOT NULL, INDEX IDX_6EEAA67DA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE donation (id INT AUTO_INCREMENT NOT NULL, description LONGTEXT DEFAULT NULL, date_don DATETIME NOT NULL, type_id INT NOT NULL, donateur_id INT NOT NULL, INDEX IDX_31E581A0C54C8C93 (type_id), INDEX IDX_31E581A0A9C80E3 (donateur_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE evenement (id INT AUTO_INCREMENT NOT NULL, titre VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, date_debut DATETIME NOT NULL, date_fin DATETIME NOT NULL, lieu VARCHAR(255) NOT NULL, nb_places INT NOT NULL, age_min INT DEFAULT NULL, age_max INT DEFAULT NULL, prix DOUBLE PRECISION DEFAULT NULL, image VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, organisateur_id INT NOT NULL, INDEX IDX_B26681ED936B2FA (organisateur_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE forum_categorie (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(150) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE forum_reponse (id INT AUTO_INCREMENT NOT NULL, contenu LONGTEXT NOT NULL, created_at DATETIME NOT NULL, sujet_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_AE7A93B67C4D497E (sujet_id), INDEX IDX_AE7A93B6A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE forum_sujet (id INT AUTO_INCREMENT NOT NULL, titre VARCHAR(200) NOT NULL, contenu LONGTEXT NOT NULL, created_at DATETIME NOT NULL, categorie_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_3667FF4ABCF5E72D (categorie_id), INDEX IDX_3667FF4AA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE ligne_commande (id INT AUTO_INCREMENT NOT NULL, quantite INT NOT NULL, prix_unitaire DOUBLE PRECISION NOT NULL, commande_id INT NOT NULL, produit_id INT NOT NULL, INDEX IDX_3170B74B82EA2E54 (commande_id), INDEX IDX_3170B74BF347EFB (produit_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE produit (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, prix DOUBLE PRECISION NOT NULL, stock INT NOT NULL, image VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE reservation (id INT AUTO_INCREMENT NOT NULL, date_reservation DATETIME NOT NULL, status VARCHAR(50) NOT NULL, participant_id INT NOT NULL, evenement_id INT NOT NULL, INDEX IDX_42C849559D1C3019 (participant_id), INDEX IDX_42C84955FD02F13 (evenement_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE type_don (id INT AUTO_INCREMENT NOT NULL, libelle VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE `user` (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, nom VARCHAR(255) NOT NULL, prenom VARCHAR(255) NOT NULL, telephone VARCHAR(20) DEFAULT NULL, created_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE commande ADD CONSTRAINT FK_6EEAA67DA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE donation ADD CONSTRAINT FK_31E581A0C54C8C93 FOREIGN KEY (type_id) REFERENCES type_don (id)');
        $this->addSql('ALTER TABLE donation ADD CONSTRAINT FK_31E581A0A9C80E3 FOREIGN KEY (donateur_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE evenement ADD CONSTRAINT FK_B26681ED936B2FA FOREIGN KEY (organisateur_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE forum_reponse ADD CONSTRAINT FK_AE7A93B67C4D497E FOREIGN KEY (sujet_id) REFERENCES forum_sujet (id)');
        $this->addSql('ALTER TABLE forum_reponse ADD CONSTRAINT FK_AE7A93B6A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE forum_sujet ADD CONSTRAINT FK_3667FF4ABCF5E72D FOREIGN KEY (categorie_id) REFERENCES forum_categorie (id)');
        $this->addSql('ALTER TABLE forum_sujet ADD CONSTRAINT FK_3667FF4AA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE ligne_commande ADD CONSTRAINT FK_3170B74B82EA2E54 FOREIGN KEY (commande_id) REFERENCES commande (id)');
        $this->addSql('ALTER TABLE ligne_commande ADD CONSTRAINT FK_3170B74BF347EFB FOREIGN KEY (produit_id) REFERENCES produit (id)');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_42C849559D1C3019 FOREIGN KEY (participant_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_42C84955FD02F13 FOREIGN KEY (evenement_id) REFERENCES evenement (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE commande DROP FOREIGN KEY FK_6EEAA67DA76ED395');
        $this->addSql('ALTER TABLE donation DROP FOREIGN KEY FK_31E581A0C54C8C93');
        $this->addSql('ALTER TABLE donation DROP FOREIGN KEY FK_31E581A0A9C80E3');
        $this->addSql('ALTER TABLE evenement DROP FOREIGN KEY FK_B26681ED936B2FA');
        $this->addSql('ALTER TABLE forum_reponse DROP FOREIGN KEY FK_AE7A93B67C4D497E');
        $this->addSql('ALTER TABLE forum_reponse DROP FOREIGN KEY FK_AE7A93B6A76ED395');
        $this->addSql('ALTER TABLE forum_sujet DROP FOREIGN KEY FK_3667FF4ABCF5E72D');
        $this->addSql('ALTER TABLE forum_sujet DROP FOREIGN KEY FK_3667FF4AA76ED395');
        $this->addSql('ALTER TABLE ligne_commande DROP FOREIGN KEY FK_3170B74B82EA2E54');
        $this->addSql('ALTER TABLE ligne_commande DROP FOREIGN KEY FK_3170B74BF347EFB');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY FK_42C849559D1C3019');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY FK_42C84955FD02F13');
        $this->addSql('DROP TABLE commande');
        $this->addSql('DROP TABLE donation');
        $this->addSql('DROP TABLE evenement');
        $this->addSql('DROP TABLE forum_categorie');
        $this->addSql('DROP TABLE forum_reponse');
        $this->addSql('DROP TABLE forum_sujet');
        $this->addSql('DROP TABLE ligne_commande');
        $this->addSql('DROP TABLE produit');
        $this->addSql('DROP TABLE reservation');
        $this->addSql('DROP TABLE type_don');
        $this->addSql('DROP TABLE `user`');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
