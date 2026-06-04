<?php

namespace App\Entity;

use App\Repository\MediaRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;

// Représente un média (image) associé à un album et à un utilisateur
#[ORM\Entity(repositoryClass: MediaRepository::class)]
class Media
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    /** @phpstan-ignore property.unusedType */
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'medias', fetch: 'EAGER')]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Album::class, fetch: 'EAGER', inversedBy: 'medias')]
    private ?Album $album = null;

    #[ORM\Column]
    private string $path;

    #[ORM\Column]
    #[Assert\NotBlank(message: 'Le titre ne peut pas être vide.')]
    #[Assert\Length(max: 150, maxMessage: 'Le titre ne peut pas dépasser {{ limit }} caractères.')]
    private string $title;

    #[Assert\File(
        maxSize: '2M',
        maxSizeMessage: 'Le fichier est trop volumineux ({{ size }} Mo). La taille maximum autorisée est {{ limit }} Mo.',
        mimeTypes: ['image/jpeg', 'image/png', 'image/webp'],
        mimeTypesMessage: 'Le fichier doit être une image (JPEG, PNG ou WebP).',
    )]
    private ?UploadedFile $file = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): void
    {
        $this->user = $user;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function setPath(string $path): void
    {
        $this->path = $path;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getFile(): ?UploadedFile
    {
        return $this->file;
    }

    public function setFile(?UploadedFile $file): void
    {
        $this->file = $file;
    }

    public function getAlbum(): ?Album
    {
        return $this->album;
    }

    public function setAlbum(?Album $album): void
    {
        $this->album = $album;
    }
}
