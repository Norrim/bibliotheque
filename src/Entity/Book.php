<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\BookRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Livre du catalogue. Les livres sont importés depuis l'API OpenLibrary et
 * synchronisés chaque nuit ; openLibraryKey sert de clé d'upsert idempotent.
 */
#[ORM\Entity(repositoryClass: BookRepository::class)]
#[ORM\Table(name: 'book')]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(fields: ['openLibraryKey'], message: 'Ce livre OpenLibrary existe déjà.')]
class Book
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private string $title;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $author = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $isbn = null;

    /**
     * Clé OpenLibrary (ex. "OL45804W"), unique : identifie le livre source.
     */
    #[ORM\Column(length: 50, unique: true)]
    #[Assert\NotBlank]
    private string $openLibraryKey;

    #[ORM\Column(length: 1024, nullable: true)]
    private ?string $coverUrl = null;

    #[ORM\Column(nullable: true)]
    private ?int $publishedYear = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    public function __construct(string $openLibraryKey, string $title)
    {
        $this->openLibraryKey = $openLibraryKey;
        $this->title = $title;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = $this->createdAt;
    }

    #[ORM\PreUpdate]
    public function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getAuthor(): ?string
    {
        return $this->author;
    }

    public function setAuthor(?string $author): static
    {
        $this->author = $author;

        return $this;
    }

    public function getIsbn(): ?string
    {
        return $this->isbn;
    }

    public function setIsbn(?string $isbn): static
    {
        $this->isbn = $isbn;

        return $this;
    }

    public function getOpenLibraryKey(): string
    {
        return $this->openLibraryKey;
    }

    public function getCoverUrl(): ?string
    {
        return $this->coverUrl;
    }

    public function setCoverUrl(?string $coverUrl): static
    {
        $this->coverUrl = $coverUrl;

        return $this;
    }

    public function getPublishedYear(): ?int
    {
        return $this->publishedYear;
    }

    public function setPublishedYear(?int $publishedYear): static
    {
        $this->publishedYear = $publishedYear;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
