<?php

namespace App\Entity;

use App\Repository\ForumCategorieRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ForumCategorieRepository::class)]
class ForumCategorie
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    private ?string $nom = null;

    /**
     * @var Collection<int, ForumSujet>
     */
    #[ORM\OneToMany(targetEntity: ForumSujet::class, mappedBy: 'categorie', orphanRemoval: true)]
    private Collection $sujets;

    public function __construct()
    {
        $this->sujets = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;
        return $this;
    }

    /**
     * @return Collection<int, ForumSujet>
     */
    public function getSujets(): Collection
    {
        return $this->sujets;
    }

    public function addSujet(ForumSujet $sujet): static
    {
        if (!$this->sujets->contains($sujet)) {
            $this->sujets->add($sujet);
            $sujet->setCategorie($this);
        }
        return $this;
    }

    public function removeSujet(ForumSujet $sujet): static
    {
        if ($this->sujets->removeElement($sujet)) {
            if ($sujet->getCategorie() === $this) {
                $sujet->setCategorie(null);
            }
        }
        return $this;
    }
}
