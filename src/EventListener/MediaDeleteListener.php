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
 * - Intercepte l'événement Doctrine preRemove sur l'entité Media, déclenché lors de la suppression d'un Media.
 * - Supprime automatiquement le fichier présent dans /public/uploads.
 * - Garantit la cohérence entre la base de données et le système de fichiers en évitant les fichiers orphelins.
 *
 * Fonctionnement :
 *  - La suppression peut venir de plusieurs endroits (controller, cascade Doctrine, commande CLI…),
 *   et le listener garantit un comportement identique dans tous les cas.
 * - Le controller ne doit pas gérer la suppression des fichiers physiques : cette responsabilité
 *   est centralisée ici pour respecter le principe DRY.
 * - preRemove() est exécuté juste avant la suppression de l'entité Media.
 * - Le listener reconstruit le chemin absolu du fichier à partir du projectDir et le supprime s'il existe.
 */
#[AsEntityListener(event: Events::preRemove, entity: Media::class)]
class MediaDeleteListener
{
    public function __construct(
        // Injection du projectDir pour construire le chemin absolu du fichier à supprimer
        #[Autowire('%kernel.project_dir%')] private readonly string $projectDir,
    ) {
    }

    public function preRemove(Media $media): void
    {
        // Construit le chemin absolu du fichier à supprimer
        $absolutePath = $this->projectDir.'/public/'.$media->getPath();

        // Vérifie si le fichier existe avant de tenter de le supprimer
        if (file_exists($absolutePath)) {
            // Supprime le fichier physique associé au Media
            unlink($absolutePath);
        }
    }
}
