<?php

namespace App\Tests\Unit\EventListener;

use App\Entity\Media;
use App\EventListener\MediaDeleteListener;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires du MediaDeleteListener.
 *
 * Ce listener supprime le fichier physique associé à un Media
 * lorsque l'entité est supprimée par Doctrine (preRemove).
 */
class MediaDeleteListenerTest extends TestCase
{
    private string $tmpDir;

    /**
     * setUp() : exécuté AVANT chaque test → prépare l'environnement de test.
     */
    protected function setUp(): void
    {
        // Création d'un répertoire temporaire simulant %kernel.project_dir%
        $this->tmpDir = sys_get_temp_dir().'/media_delete_test_'.uniqid();
        // Recréation de la structure attendue par le listener pour tester suppréssion dans /public/uploads
        mkdir($this->tmpDir.'/public/uploads', 0777, true); // 0777 pour s'assurer que les permissions sont suffisantes pour les tests
    }

    /**
     * tearDown() : exécuté APRÈS chaque test → nettoie l'environnement de test.
     */
    protected function tearDown(): void
    {
        // Nettoyage du répertoire temporaire pour éviter toute interférence entre tests.
        $this->removeDir($this->tmpDir);
    }

    /**
     * Quand le fichier physique existe, preRemove() doit le supprimer.
     *
     * Raison métier :
     * - Évite d'accumuler des fichiers orphelins dans /public/uploads.
     * - La suppression en base et sur disque doit être atomique.
     */
    public function testPreRemoveDeletesExistingFile(): void
    {
        // Chemin relatif stocké dans l'entité Media
        $path = 'uploads/photo.jpg';
        // Création d'un fichier physique simulant le média à supprimer
        $absolutePath = $this->tmpDir.'/public/'.$path;
        // Création du fichier avec du contenu factice pour s'assurer qu'il existe
        file_put_contents($absolutePath, 'fake image content');

        // Création d'un Media avec un chemin valide pointant vers le fichier créé
        $media = new Media();
        $media->setPath($path);

        // Instanciation du listener avec le projectDir pointant vers notre répertoire temporaire projectDir
        $listener = new MediaDeleteListener($this->tmpDir);
        // Appel de la méthode preRemove qui doit supprimer le fichier physique
        $listener->preRemove($media);

        // Vérifie que le fichier a bien été supprimé du système de fichiers
        $this->assertFileDoesNotExist($absolutePath);
    }

    /**
     * Quand le fichier physique n'existe pas, preRemove() ne doit pas lever d'erreur.
     *
     * Raison métier :
     * - Un fichier peut avoir été supprimé manuellement sur le serveur.
     * - Le listener doit gérer ce cas sans provoquer d'exception.
     */
    public function testPreRemoveDoesNotThrowWhenFileIsMissing(): void
    {
        // Création d'un Media avec un chemin pointant vers un fichier qui n'existe pas
        $media = new Media();
        $media->setPath('uploads/fichier_inexistant.jpg');

        // Instanciation du listener avec le projectDir pointant vers notre répertoire temporaire projectDir
        $listener = new MediaDeleteListener($this->tmpDir);

        // Aucune exception  ne doit être levée même si le fichier n'existe pas
        $this->expectNotToPerformAssertions();
        $listener->preRemove($media);
    }

    // --- Helpers ---

    /**
     * Suppression récursive d'un répertoire.
     * Utilisé pour nettoyer le répertoire temporaire après chaque test.
     */
    private function removeDir(string $dir): void
    {
        // Si le chemin n'est pas un dossier, rien à supprimer.
        if (!is_dir($dir)) {
            return;
        }
        // Parcourt tous les éléments du dossier (fichiers + sous-dossiers).
        foreach (scandir($dir) as $item) {
            if ('.' === $item || '..' === $item) {
                continue; //  On ignore les entrées système . et .. pour éviter de remonter dans l'arborescence ou de supprimer le dossier lui-même prématurément
            }
            $path = $dir.'/'.$item;
            // Si c'est un dossier → suppression récursive
            // Si c'est un fichier → unlink()
            is_dir($path) ? $this->removeDir($path) : unlink($path);
        }
        // Suppression du dossier lui-même après avoir supprimé son contenu
        rmdir($dir);
    }
}
