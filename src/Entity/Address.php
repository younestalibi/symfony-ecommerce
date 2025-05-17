<?php

namespace App\Entity;

use App\Repository\AddressRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AddressRepository::class)]
class Address
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Address line 1 cannot be blank.")]
    #[Assert\Length(
        max: 255,
        maxMessage: "Address line 1 cannot be longer than {{ limit }} characters."
    )]
    private ?string $line1 = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(
        max: 255,
        maxMessage: "Address line 2 cannot be longer than {{ limit }} characters."
    )]
    private ?string $line2 = null;

    #[ORM\Column(length: 30)]
    #[Assert\NotBlank(message: "City is required.")]
    #[Assert\Length(
        max: 30,
        maxMessage: "City name cannot be longer than {{ limit }} characters."
    )]
    private ?string $city = null;

    #[ORM\Column(length: 30)]
    #[Assert\NotBlank(message: "Country is required.")]
    #[Assert\Length(
        max: 30,
        maxMessage: "Country name cannot be longer than {{ limit }} characters."
    )]
    private ?string $country = null;

    #[ORM\Column(length: 30)]
    #[Assert\NotBlank(message: "Zip code is required.")]
    #[Assert\Length(
        max: 30,
        maxMessage: "Zip code cannot be longer than {{ limit }} characters."
    )]
    private ?string $zipCode = null;

    #[ORM\ManyToOne(inversedBy: 'addresses')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column]
    #[Assert\NotNull(message: "isDefault flag must be set.")]
    private ?bool $isDefault = null;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLine1(): ?string
    {
        return $this->line1;
    }

    public function setLine1(string $line1): static
    {
        $this->line1 = $line1;

        return $this;
    }

    public function getLine2(): ?string
    {
        return $this->line2;
    }

    public function setLine2(?string $line2): static
    {
        $this->line2 = $line2;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(string $city): static
    {
        $this->city = $city;

        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(string $country): static
    {
        $this->country = $country;

        return $this;
    }

    public function getZipCode(): ?string
    {
        return $this->zipCode;
    }

    public function setZipCode(string $zipCode): static
    {
        $this->zipCode = $zipCode;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function isDefault(): ?bool
    {
        return $this->isDefault;
    }

    public function setIsDefault(bool $isDefault): static
    {
        $this->isDefault = $isDefault;

        return $this;
    }
}
