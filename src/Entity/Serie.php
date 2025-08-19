<?php

namespace App\Entity;

use App\Repository\SerieRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SerieRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\UniqueConstraint(columns: ['name', 'first_air_date'])] // Décommenter si vous voulez la contrainte DB
#[UniqueEntity(fields: ['name', 'firstAirDate'], message: 'Une série avec ce nom et cette date de première diffusion existe déjà.')]
class Serie
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)] // Correction : 'unique: true' a été retiré, car l'unicité combinée est gérée par UniqueEntity
    #[Assert\NotBlank(message: 'Ce champ ne peut pas être vide.')]
    #[Assert\Length(
        min: 2,
        max: 20,
        minMessage: 'Le nom doit contenir au moins {{ limit }} caractères.',
        maxMessage: 'Le nom doit contenir au plus {{ limit }} caractères.'
    )]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $overview = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le statut ne peut pas être vide.')]
    #[Assert\Choice(choices: ['returning', 'ended', 'Canceled'], message: 'Ce choix de statut n\'est pas valide.')]
    private ?string $status = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Range(min: 1, max: 10, notInRangeMessage: 'Les votes doivent être compris entre {{ min }} et {{ max }}.')] // Correction : virgule de fin retirée
    private ?float $vote = null;

    #[ORM\Column(nullable: true)]
    private ?float $popularity = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $genres = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    #[Assert\LessThan('-3 days', message: 'La date de lancement doit être antérieure au {{ compared_value }}.')]
    private ?\DateTime $firstAirDate = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)] // <-- **CORRECTION MAJEURE ICI** : Ajout de l'annotation manquante
    #[Assert\GreaterThan(propertyPath: 'firstAirDate', message: 'La date de dernière diffusion doit être postérieure à la date de première diffusion.')]
    // Validation 1 : Si le statut est 'ended', lastAirDate doit être non vide
    #[Assert\When(
        expression: "this.getStatus() == 'ended'",
        constraints: [
            new Assert\NotBlank(message: 'La date de dernière diffusion est requise pour une série "Terminé".')
        ]
    )]
    // Validation 2 : Si le statut est 'returning' ou 'Canceled', lastAirDate doit être NULL
    #[Assert\When(
        expression: "this.getStatus() == 'returning' || this.getStatus() == 'Canceled'",
        constraints: [
            new Assert\IsNull(message: 'La date de dernière diffusion ne doit pas être renseignée pour une série "En cours" ou "Abandonné".')
        ]
    )]
    private ?\DateTime $lastAirDate = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $backdrop = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $poster = null;

    #[ORM\Column(nullable: true)]
    private ?int $tmdbId = null;

    #[ORM\Column]
    private ?\DateTime $dateCreated = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $dateModified = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getOverview(): ?string
    {
        return $this->overview;
    }

    public function setOverview(?string $overview): static
    {
        $this->overview = $overview;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getVote(): ?float
    {
        return $this->vote;
    }

    public function setVote(?float $vote): static
    {
        $this->vote = $vote;

        return $this;
    }

    public function getPopularity(): ?float
    {
        return $this->popularity;
    }

    public function setPopularity(?float $popularity): static
    {
        $this->popularity = $popularity;

        return $this;
    }

    public function getGenres(): ?string
    {
        return $this->genres;
    }

    public function setGenres(?string $genres): static
    {
        $this->genres = $genres;

        return $this;
    }

    public function getFirstAirDate(): ?\DateTime
    {
        return $this->firstAirDate;
    }

    public function setFirstAirDate(?\DateTime $firstAirDate): static
    {
        $this->firstAirDate = $firstAirDate;

        return $this;
    }

    public function getLastAirDate(): ?\DateTime
    {
        return $this->lastAirDate;
    }

    public function setLastAirDate(?\DateTime $lastAirDate): static
    {
        $this->lastAirDate = $lastAirDate;

        return $this;
    }

    public function getBackdrop(): ?string
    {
        return $this->backdrop;
    }

    public function setBackdrop(?string $backdrop): static
    {
        $this->backdrop = $backdrop;

        return $this;
    }

    public function getPoster(): ?string
    {
        return $this->poster;
    }

    public function setPoster(?string $poster): static
    {
        $this->poster = $poster;

        return $this;
    }

    public function getTmdbId(): ?int
    {
        return $this->tmdbId;
    }

    public function setTmdbId(?int $tmdbId): static
    {
        $this->tmdbId = $tmdbId;

        return $this;
    }

    public function getDateCreated(): ?\DateTime
    {
        return $this->dateCreated;
    }

    #[ORM\PrePersist]
    public function setDateCreatedValue(): void
    {
        $this->dateCreated = new \DateTime();
    }

    public function getDateModified(): ?\DateTime
    {
        return $this->dateModified;
    }

    #[ORM\PreUpdate]
    public function setDateModifiedValue(): void
    {
        $this->dateModified = new \DateTime();
    }
}
