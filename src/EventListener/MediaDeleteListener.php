<?php

namespace App\EventListener;

use App\Entity\Media;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Listener Doctrine chargé de supprimer le fichier physique associé à un média.
 *
 * Rôle :
 * - Intercepte l'événement Doctrine preRemove sur l'entité Media.
 * - Supprime automatiquement le fichier présent dans /public/uploads.
 * - Garantit la cohérence entre la base de données et le système de fichiers.
 *
 * Fonctionnement :
 * - preRemove() est exécuté juste avant la suppression de l'entité Media.
 * - Le listener calcule le chemin absolu du fichier et le supprime s'il existe.
 */
#[AsEntityListener(event: Events::preRemove, entity: Media::class)]
class MediaDeleteListener
{
    public function __construct(
        #[Autowire('%kernel.project_dir%')] private readonly string $projectDir,
    ) {}

    public function preRemove(Media $media): void
    {
        $absolutePath = $this->projectDir . '/public/' . $media->getPath();

        if (file_exists($absolutePath)) {
            unlink($absolutePath);
        }
    }
}
