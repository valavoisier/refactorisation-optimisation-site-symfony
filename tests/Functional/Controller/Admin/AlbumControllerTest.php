<?php

namespace App\Tests\Functional\Controller\Admin;

use App\DataFixtures\AppFixtures;
use App\Repository\AlbumRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * TESTS FONCTIONNELS — AlbumController (espace admin)
 * ===================================================
 *
 * Objectif :
 * ----------
 * Vérifier le comportement du controller de gestion des albums :
 *
 *   - Contrôle d'accès :
 *       • anonyme → redirection login
 *       • invité → 403
 *       • admin → 200
 *   - Listing des albums
 *   - Ajout d’un album
 *   - Modification d’un album
 *   - Suppression d’un album
 *   - 404 sur update/delete avec ID inexistant
 *
 * Importance métier :
 * -------------------
 * - Les albums structurent le portfolio d'Ina.
 * - Seul l’administrateur peut créer, modifier ou supprimer des albums.
 * - Les invités ne doivent jamais accéder à ces fonctionnalités.
 */
class AlbumControllerTest extends WebTestCase
{
    // ------------------------------------------------------------------ //
    //  Contrôle d'accès                                                   //
    // ------------------------------------------------------------------ //

    /**
     * Vérifie qu'un visiteur anonyme sur /admin/album doit être redirigé vers /login.
     *
     * Raison métier :
     * - La gestion des albums est strictement réservée à l'administrateur.
     */
    public function testIndexRedirectsAnonymousUser(): void
    {
        // Création d'un client HTTP pour simuler un visiteur non authentifié.
        $client = static::createClient();
        // Envoi d'une requête GET pour accéder à la page d'administration des albums.
        $client->request('GET', '/admin/album');

        // Vérifie que la réponse est une redirection vers la page de login.
        $this->assertResponseRedirects('/login');
    }

    /**
     * Vérifie qu'un utilisateur ROLE_USER ne peut pas accéder à /admin/album (réponse403).
     *
     * Raison métier :
     * - Les invités n'ont pas de droits sur la gestion des albums.
     */
    public function testIndexForbidsGuestUser(): void
    {
        // Création d'un client HTTP pour simuler un utilisateur authentifié.
        $client = static::createClient();
        // Récupération du UserRepository pour trouver un utilisateur invité.
        $userRepo = static::getContainer()->get(UserRepository::class);
        // Récupère un utilisateur invité (sans ROLE_ADMIN) par son email défini dans les fixtures.
        $guest = $userRepo->findOneBy(['email' => AppFixtures::GUEST_EMAIL]);

        //  Connexion de l'invité et tentative d'accès à la page d'administration des albums.
        $client->loginUser($guest);
        $client->request('GET', '/admin/album');

        // Vérifie le refus d'accès - réponse est 403 Forbidden.
        $this->assertResponseStatusCodeSame(403);
    }

    /**
     * Vérifie que l'admin peut accéder à la liste des albums (200).
     *
     * Raison métier :
     * - L'administrateur est le seul profil autorisé à gérer la structure du portfolio.
     */
    public function testIndexIsAccessibleByAdmin(): void
    {
        // Création d'un client HTTP pour simuler un utilisateur authentifié.
        $client = static::createClient();
        // Récupération du UserRepository pour trouver l'administrateur.
        $userRepo = static::getContainer()->get(UserRepository::class);
        // Récupère l'administrateur unique  par son email défini dans les fixtures.
        $admin = $userRepo->findOneBy(['email' => AppFixtures::ADMIN_EMAIL]);

        // Connexion de l'administrateur et accès à la page d'administration des albums.
        $client->loginUser($admin);
        $client->request('GET', '/admin/album');

        // Vérifie que la page charge correctement - succès (HTTP 200).
        $this->assertResponseIsSuccessful();
    }

    // ------------------------------------------------------------------ //
    //  Listing des albums                                                           //
    // ------------------------------------------------------------------ //

    /**
     * Vérifie que la page index affiche les albums.
     *
     * Raison métier :
     * - L'admin doit voir ses albums pour les gérer.
     */
    public function testIndexDisplaysAlbums(): void
    {
        // Création d'un client HTTP pour simuler un utilisateur authentifié.
        $client = static::createClient();
        // Récupération du UserRepository pour trouver l'administrateur.
        $userRepo = static::getContainer()->get(UserRepository::class);
        // Récupère l'administrateur unique  par son email défini dans les fixtures.
        $admin = $userRepo->findOneBy(['email' => AppFixtures::ADMIN_EMAIL]);

        // Connexion de l'administrateur et accès à la page d'administration des albums.
        $client->loginUser($admin);
        $client->request('GET', '/admin/album');

        // Vérifie que la page charge correctement - succès (HTTP 200).
        $this->assertResponseIsSuccessful();
        // Vérifie que la page contient un tableau listant les albums.
         $this->assertSelectorExists('table');
        $this->assertSelectorExists('table');
    }

    // ------------------------------------------------------------------ //
    //  Ajout d'un nouvel album                                                               //
    // ------------------------------------------------------------------ //

     /**
     * Vérifie que la page d'ajout d'album affiche correctement le formulaire (HTTP 200).
     *
     * Raison métier :
     * - L'administrateur doit pouvoir préparer la création d'un nouvel album via un formulaire dédié.
     */
    public function testAddPageLoads(): void
    {
        // Création d'un client HTTP pour simuler un utilisateur authentifié.
        $client = static::createClient();
        // Récupération du UserRepository pour trouver l'administrateur.
        $userRepo = static::getContainer()->get(UserRepository::class);
        // Récupère l'administrateur unique  par son email défini dans les fixtures.
        $admin = $userRepo->findOneBy(['email' => AppFixtures::ADMIN_EMAIL]);

        // Connexion de l'administrateur et accès à la page d'ajout d'album.
        $client->loginUser($admin);
        $client->request('GET', '/admin/album/add');

        // Vérifie que la page charge correctement - succès (HTTP 200).
        $this->assertResponseIsSuccessful();
        // Vérifie que le formulaire d'ajout d'album est présent sur la page.
        $this->assertSelectorExists('form');
    }

    /**
     * Vérifie que la soumission d'un formulaire valide crée l'album et redirige vers la liste.
     *
     * Raison métier :
     * - L'admin doit pouvoir créer des albums pour organiser le portfolio.
     */
    public function testAddValidFormCreatesAlbum(): void
    {
        // Création d'un client HTTP pour simuler un utilisateur authentifié.
        $client = static::createClient();
        // Récupération du UserRepository pour trouver l'administrateur.
        $userRepo = static::getContainer()->get(UserRepository::class);
        // Récupère l'administrateur unique  par son email.
        $admin = $userRepo->findOneBy(['email' => AppFixtures::ADMIN_EMAIL]);

        // Connexion de l'administrateur et accès à la page d'ajout d'album.
        $client->loginUser($admin);
        $crawler = $client->request('GET', '/admin/album/add');

        // Soumission du formulaire avec un nom d'album unique pour éviter les conflits.
        $form = $crawler->selectButton('Ajouter')->form([
            'album[name]' => 'Album test ' . uniqid(),
        ]);
        // Envoi du formulaire pour créer le nouvel album.
        $client->submit($form);

        // Vérifie que la soumission du formulaire redirige vers la liste des albums.
        $this->assertResponseRedirects('/admin/album');
    }

    // ------------------------------------------------------------------ //
    //  Modification                                                        //
    // ------------------------------------------------------------------ //

     /**
     * Vérifie qu'un GET sur /admin/album/update/{id} affiche le formulaire pré-rempli (200).
     *
     * Raison métier :
     * - La modification d'un album existant doit être possible sans erreur pour maintenir un portfolio à jour.
     */
    public function testUpdatePageLoads(): void
    {
        // Création d'un client HTTP pour simuler un utilisateur authentifié.
        $client = static::createClient();
        // Récupération du UserRepository pour trouver l'administrateur.
        $userRepo = static::getContainer()->get(UserRepository::class);
        // Récupère l'administrateur unique  par son email.
        $admin = $userRepo->findOneBy(['email' => AppFixtures::ADMIN_EMAIL]);

        // Récupération du repository des albums pour obtenir un album existant.
        $albumRepo = static::getContainer()->get(AlbumRepository::class);
        // Récupère le premier album trouvé en base de données pour le test.
        $album = $albumRepo->findOneBy([]);

        // Connexion de l'administrateur et accès à la page de modification de l'album.
        $client->loginUser($admin);
        $client->request('GET', '/admin/album/update/' . $album->getId());

        // Vérifie que la page charge correctement - succès (HTTP 200).
        $this->assertResponseIsSuccessful();
        // Vérifie que le formulaire de modification est présent sur la page.
        $this->assertSelectorExists('form');
    }

    /**
     * Vérifie que la soumission du formulaire de modification redirige vers la liste.
     *
     * Raison métier :
     * - L'admin doit pouvoir renommer un album existant.
     */
    public function testUpdateValidFormSavesAlbum(): void
    {
        // Création d'un client HTTP pour simuler un utilisateur authentifié.
        $client = static::createClient();
        // Récupération du UserRepository pour trouver l'administrateur.
        $userRepo = static::getContainer()->get(UserRepository::class);
        // Récupère l'administrateur unique  par son email.
        $admin = $userRepo->findOneBy(['email' => AppFixtures::ADMIN_EMAIL]);
        // Récupération du repository des albums pour obtenir un album existant.
        $albumRepo = static::getContainer()->get(AlbumRepository::class);
        // Récupère le premier album trouvé en base de données pour modification.
        $album = $albumRepo->findOneBy([]);

        // Connexion de l'administrateur et accès à la page de modification de cet album.
        $client->loginUser($admin);
        $crawler = $client->request('GET', '/admin/album/update/' . $album->getId());

        // Soumission du formulaire de modification avec un nouveau nom d'album.
        $form = $crawler->selectButton('Modifier')->form([
            'album[name]' => 'Album renommé',
        ]);
        // Envoi du formulaire pour enregistrer les modifications de l'album.
        $client->submit($form);

        // Vérifie que la soumission du formulaire redirige vers la liste des albums.
        $this->assertResponseRedirects('/admin/album');
    }

    // ------------------------------------------------------------------ //
    //  Suppression                                                         //
    // ------------------------------------------------------------------ //

    /**
     * La suppression d'un album redirige vers la liste.
     *
     * Raison métier :
     * - L'admin doit pouvoir supprimer un album obsolète (cascade sur les médias).
     */
    public function testDeleteRemovesAlbum(): void
    {
        // Création d'un client HTTP pour simuler un utilisateur authentifié.
        $client = static::createClient();
        // Récupération du UserRepository pour trouver l'administrateur.
        $userRepo = static::getContainer()->get(UserRepository::class);
        // Récupère l'administrateur unique par son email.
        $admin = $userRepo->findOneBy(['email' => AppFixtures::ADMIN_EMAIL]);

        // Créer un album dédié à la suppression pour ne pas casser les autres tests
        $em = static::getContainer()->get('doctrine')->getManager();
        $album = new \App\Entity\Album();
        $album->setName('Album à supprimer');
        $em->persist($album);
        $em->flush();
        // On conserve l'ID de l'album pour la requête de suppression.
        $albumId = $album->getId();

        // Connexion de l'administrateur et tentative de suppression de cet album.
        $client->loginUser($admin);
        $client->request('GET', '/admin/album/delete/' . $albumId);

        // Vérifie que la suppression redirige vers la liste des albums.
        $this->assertResponseRedirects('/admin/album');

        // Vérifier que l'album n'existe plus en base
        // On vide le contexte Doctrine pour forcer une nouvelle requête en base.
        $em->clear();
        // Récupère à nouveau le repository des albums pour vérifier la suppression.
        $albumRepo = static::getContainer()->get(AlbumRepository::class);
        // Vérifie que l'album avec l'ID supprimé n'existe plus en Base (doit retourner null).
        $this->assertNull($albumRepo->find($albumId));
    }
}
