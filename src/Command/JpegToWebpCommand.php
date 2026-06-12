<?php

/**
 * Commande Symfony permettant de convertir automatiquement toutes les images
 * JPEG/JPG présentes dans le dossier public/uploads en format WebP.
 *
 * Fonctionnement :
 *  - Scan du dossier public/uploads pour détecter les fichiers .jpg/.jpeg (toutes casses)
 *  - Conversion en WebP via l’extension GD (qualité 80)
 *  - Mise à jour du chemin dans la base de données pour les entités Media concernées
 *    (ex : uploads/photo.jpg → uploads/photo.webp)
 *  - Suppression du fichier JPEG d’origine après conversion réussie
 *
 * Cette commande permet d’optimiser le poids des images et d’améliorer les performances
 * du site en utilisant le format WebP, plus léger et plus moderne.
 *
 * Documentation Symfony Console :
 * https://symfony.com/doc/current/console.html
 */

namespace App\Command;

use App\Repository\MediaRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'app:jpeg-to-webp',
    description: 'Convertit toutes les images JPEG/JPG du dossier public/uploads en WebP.',
)]
class JpegToWebpCommand
{
    public function __construct(
        // Injecte le chemin du projet pour accéder à public/uploads
        #[Autowire('%kernel.project_dir%')] private readonly string $projectDir,
        // Injecte le repository pour récupérer les entités Media correspondant aux fichiers convertis
        private readonly MediaRepository $mediaRepository,
        // Injecte l'EntityManager pour persister les changements dans la base de données
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function __invoke(InputInterface $input, OutputInterface $output): int
    {
        // Utilise SymfonyStyle pour une meilleure présentation des messages dans la console
        $io = new SymfonyStyle($input, $output);
        // Définit le chemin du dossier contenant les images à convertir public/uploads
        $uploadsDir = $this->projectDir.'/public/uploads';

        // Recherche de tous les fichiers JPEG/JPG (4 variantes de casse)
        $files = array_merge(
            glob($uploadsDir.'/*.jpg') ?: [],
            glob($uploadsDir.'/*.jpeg') ?: [],
            glob($uploadsDir.'/*.JPG') ?: [],
            glob($uploadsDir.'/*.JPEG') ?: [],
        );

        // Si aucun fichier n'est trouvé, affiche un message et termine la commande
        if ([] === $files) {
            $io->info('Aucun fichier JPEG/JPG trouvé dans public/uploads.');

            // Retourne SUCCESS même s'il n'y a rien à convertir, car ce n'est pas une erreur
            return Command::SUCCESS;
        }

        // Compteurs pour suivre le nombre de conversions réussies et d'erreurs
        $converted = 0;
        $errors = 0;

        // Parcourt chaque fichier JPEG trouvé et tente de le convertir en WebP
        foreach ($files as $jpegPath) {
            // Génère le nom du fichier WebP en remplaçant l'extension .jpg/.jpeg par .webp
            $filename = basename($jpegPath);
            // Utilise une expression régulière pour remplacer l'extension de manière insensible à la casse jpg|jpeg
            // Nouveau nom de fichier avec l'extension .webp
            $webpFilename = (string) preg_replace('/\.(jpg|jpeg)$/i', '.webp', $filename);
            // Chemin complet du fichier WebP à créer
            $webpPath = $uploadsDir.'/'.$webpFilename;

            // Tentative de chargement du JPEG via GD
            // @ supprime les warnings en cas de fichier corrompu ou non lisible, on gère l'erreur avec un message personnalisé
            $image = @imagecreatefromjpeg($jpegPath);
            if (false === $image) {
                $io->warning("Impossible de lire : $filename");
                ++$errors;
                continue;
            }

            // Tentative de conversion en WebP avec une qualité de 80 (sur une échelle de 0 à 100)
            // Retourne false si la conversion échoue
            if (!imagewebp($image, $webpPath, 80)) {
                $io->warning("Impossible de convertir : $filename");
                ++$errors;
                continue;
            }
            // Recherche en base les entités Media qui pointent vers ce fichier JPEG
            // Le path en base est stocké sous la forme : uploads/nom.jpg
            $medias = $this->mediaRepository->findBy(['path' => 'uploads/'.$filename]);
            // Met à jour le path de chaque entité Media pour pointer vers le fichier WebP
            foreach ($medias as $media) {
                $media->setPath('uploads/'.$webpFilename);
            }

            // Supprime le fichier JPEG d'origine après conversion réussie
            unlink($jpegPath);

            // Affiche un message indiquant la conversion réussie
            $io->text("Converti : $filename → $webpFilename");
            ++$converted;
        }

        // Si au moins une image a été convertie, on persiste les changements en base de données
        if ($converted > 0) {
            $this->em->flush();
        }

        // Affiche un résumé du nombre d'images converties et d'erreurs rencontrées
        $io->success(sprintf('%d image(s) convertie(s), %d erreur(s).', $converted, $errors));

        // Retourne SUCCESS pour indiquer que la commande s'est exécutée correctement, même s'il y a eu des erreurs de conversion
        return Command::SUCCESS;
    }
}
