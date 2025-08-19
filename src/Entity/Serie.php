<?php

namespace App\Entity;

use App\Repository\SerieRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SerieRepository::class)]
#[ORM\HasLifecycleCallbacks]
//#[ORM\UniqueConstraint(columns: ['name', 'first_air_date'])]
#[UniqueEntity(fields: ['name', 'firstAirDate'], message: 'Une série avec ce nom et cette date de première diffusion existe déjà.')]
class Serie
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Ce champ ne peut pas être vide')]
    #[Assert\Length(
        max: 20,
        min: 2,
        minMessage: 'Le nom doit contenir au moins {{ limit }} caractères.',
        maxMessage: 'Le nom doit contenir au plus {{ limit }} caractères.')]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $overview = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le statut ne peut pas être vide.')]
    #[Assert\Choice(choices: ['returning', 'ended', 'Canceled'], message: 'Ce choix n\'est pas valide')]
    private ?string $status = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Range(notInRangeMessage: 'Les votes doivent être compris entre {{ min }} et {{ max }}', min: 1, max: 10, )]
    private ?float $vote = null;

    #[ORM\Column(nullable: true)]
    private ?float $popularity = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $genres = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    #[Assert\LessThan('-3 days', message: 'La date de lancement doit être antérieure au {{ compared_value }}')]
    private ?\DateTime $firstAirDate = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    #[Assert\GreaterThan(propertyPath: 'firstAirDate')]
    #[Assert\When(
        expression: "this.getStatus() == 'ended' || this.getStatus() == 'Canceled'",
        constraints: [
            new Assert\NotBlank(message: 'Date de fin requise étant donné le statut')
        ]
    )]
    #[Assert\When(
        expression: "this.getStatus() == 'returning'",
        constraints: [
            new Assert\NotBlank(message: 'Date de fin non-attendue étant donné le statut')
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

    // Quand persistée par Doctrine, cette méthode doit se déclencher automatiquement
    // Donc ajout de PrePersist
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
