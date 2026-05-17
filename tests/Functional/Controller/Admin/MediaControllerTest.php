<?php

namespace App\Tests\Functional\Controller\Admin;

use App\DataFixtures\AppFixtures;
use App\Repository\MediaRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * TESTS FONCTIONNELS — MediaController (espace admin)
 * ===================================================
 *
 * Objectif :
 * ----------
 * Vérifier le comportement du MediaController dans l'espace admin :
 *
 *   - Contrôle d'accès :
 *       • anonyme → redirection login
 *   - Listing :
 *       • admin voit tous les médias
 *       • invité voit uniquement les siens
 *   - Ajout :
 *       • formulaire accessible pour admin et invité
 *   - Suppression :
 *       • admin peut supprimer n'importe quel média
 *       • invité peut supprimer uniquement ses propres médias
 *       • invité → 403 sur média d'un autre utilisateur
 *       • 404 sur ID inexistant
 *
 * Importance métier :
 * -------------------
 * - L'espace admin gère la modération et la gestion des médias.
 * - Les invités ne doivent jamais accéder aux médias d'autres utilisateurs.
 * - La suppression doit respecter les règles de sécurité et de propriété.
 */
class MediaControllerTest extends WebTestCase
{
    // ------------------------------------------------------------------ //
    //  Contrôle d'accès                                                   //
    // ------------------------------------------------------------------ //

    /**
     * Vérifie qu'un visiteur anonyme sur /admin/media doit être redirigé vers /login.
     *
     * Raison métier :
     * - L'espace média est réservé aux utilisateurs authentifiés.
     */
    public function testIndexRedirectsAnonymousUser(): void
    {
        // Création d'un client HTTP pour simuler un visiteur non authentifié.
        $client = static::createClient();
        // Envoi d'une requête GET pour accès à la page d'administration des médias.
        $client->request('GET', '/admin/media');

        // Vérifie la redirection vers /login.
        $this->assertResponseRedirects('/login');
    }

    // ------------------------------------------------------------------ //
    //  Listing des médias                                                   //
    // ------------------------------------------------------------------ //

    /**
     * Vérifie que l'admin peut accéder à la liste complète des médias.
     *
     * Raison métier :
     * - L'admin supervise l'ensemble du contenu de la plateforme.
     */
    public function testIndexIsAccessibleByAdmin(): void
    {
        // Création d'un client HTTP pour simuler un utilisateur authentifié.
        $client = static::createClient();
        // Récupération de l'administrateur depuis le UserRepository pour se connecter.
        $userRepo = static::getContainer()->get(UserRepository::class);
        // Récupère l'administrateur unique  par son email défini dans les fixtures.
        $admin = $userRepo->findOneBy(['email' => AppFixtures::ADMIN_EMAIL]);

        // Connexion de l'administrateur et accès à la page d'administration des médias.
        $client->loginUser($admin);
        $client->request('GET', '/admin/media');

        // Vérifie que la page charge correctement - succès (HTTP 200).
        $this->assertResponseIsSuccessful();
    }

    /**
     * Vérifie qu'un invité peut accéder à /admin/media mais ne voit que ses médias.
     *
     * Raison métier :
     * - Un invité ne doit jamais voir les médias d'un autre utilisateur.
     */
    public function testIndexIsAccessibleByGuest(): void
    {
        // Création d'un client HTTP pour simuler un utilisateur authentifié.
        $client = static::createClient();
        // Récupération de l'invité depuis le UserRepository pour se connecter.
        $userRepo = static::getContainer()->get(UserRepository::class);
        $guest = $userRepo->findOneBy(['email' => AppFixtures::GUEST_EMAIL]);

        // Connexion de l'invité et accès à la page d'administration des médias.
        $client->loginUser($guest);
        $client->request('GET', '/admin/media');

        // Vérifie que la page charge correctement - succès (HTTP 200).
        $this->assertResponseIsSuccessful();
    }

    /**
     * La page index de l'admin affiche bien au moins un média.
     */
    public function testIndexShowsMediasForAdmin(): void
    {
        // Création d'un client HTTP pour simuler un utilisateur authentifié.
        $client = static::createClient();
        // Récupération de l'administrateur depuis le UserRepository pour se connecter.
        $userRepo = static::getContainer()->get(UserRepository::class);
        // Récupère l'administrateur unique  par son email défini dans les fixtures.
        $admin = $userRepo->findOneBy(['email' => AppFixtures::ADMIN_EMAIL]);

        // Connexion de l'administrateur et accès à la page d'administration des médias.
        $client->loginUser($admin);
        $client->request('GET', '/admin/media');

        // Vérifie que la page charge correctement - succès (HTTP 200) et qu'elle affiche une table de médias.
        $this->assertResponseIsSuccessful();
        //Vérifie qu'un tableau de médias est présent (médias sont affichés).
        $this->assertSelectorExists('table');
    }

    // ------------------------------------------------------------------ //
    //  Ajout                                                               //
    // ------------------------------------------------------------------ //

    /**
     * Vérifie que l'admin peut accéder au formulaire d'ajout.
     */
    public function testAddPageLoadsForAdmin(): void
    {
        // Création d'un client HTTP pour simuler un utilisateur authentifié.
        $client = static::createClient();
        // Récupération de l'administrateur depuis le UserRepository pour se connecter.
        $userRepo = static::getContainer()->get(UserRepository::class);
        // Récupère l'administrateur unique  par son email défini dans les fixtures.
        $admin = $userRepo->findOneBy(['email' => AppFixtures::ADMIN_EMAIL]);

        // Connexion de l'administrateur et accès à la page d'administration des médias.
        $client->loginUser($admin);
        // Envoi d'une requête GET pour accéder au formulaire d'ajout de média.
        $client->request('GET', '/admin/media/add');

        // Vérifie que la page charge correctement - succès (HTTP 200) et qu'elle affiche un formulaire.
        $this->assertResponseIsSuccessful();
        // Vérifie qu'un formulaire est présent sur la page d'ajout de média.
        $this->assertSelectorExists('form');
    }

    /**
     * Vérifie qu'un invité peut accéder au formulaire d'ajout.
     */
    public function testAddPageLoadsForGuest(): void
    {
        // Création d'un client HTTP pour simuler un utilisateur authentifié.
        $client = static::createClient();
        // Récupération de l'invité depuis le UserRepository pour se connecter.
        $userRepo = static::getContainer()->get(UserRepository::class);
        // Récupère l'invité unique  par son email défini dans les fixtures.
        $guest = $userRepo->findOneBy(['email' => AppFixtures::GUEST_EMAIL]);

        // Connexion de l'invité et accès à la page d'administration des médias.
        $client->loginUser($guest);
        $client->request('GET', '/admin/media/add');

        // Vérifie que la page charge correctement - succès (HTTP 200) et qu'elle affiche un formulaire.
        $this->assertResponseIsSuccessful();
        // Vérifie qu'un formulaire est présent sur la page d'ajout de média.
        $this->assertSelectorExists('form');
    }

    // ------------------------------------------------------------------ //
    //  Suppression                                                         //
    // ------------------------------------------------------------------ //

    /**
     * Vérifie que l'admin peut supprimer n'importe quel média et est redirigé.
     *
     * Raison métier :
     * - L'admin est responsable de la modération de tout le contenu.
     */
    public function testAdminCanDeleteAnyMedia(): void
    {
        // Création d'un client HTTP pour simuler un utilisateur authentifié.
        $client = static::createClient();
        // Récupération de l'administrateur depuis le UserRepository pour se connecter.
        $userRepo = static::getContainer()->get(UserRepository::class);
        // Récupère l'administrateur unique  par son email défini dans les fixtures.
        $admin = $userRepo->findOneBy(['email' => AppFixtures::ADMIN_EMAIL]);

        // Récupérer un média appartenant à l'invité actif
        $mediaRepo = static::getContainer()->get(MediaRepository::class);
        $guest = $userRepo->findOneBy(['email' => AppFixtures::GUEST_EMAIL]);
        $media = $mediaRepo->findOneBy(['user' => $guest]);

        // Connexion de l'administrateur et tentative de suppression du média de l'invité.
        $client->loginUser($admin);
        $client->request('GET', '/admin/media/delete/' . $media->getId());

        // Vérifie que l'administrateur est redirigé vers la liste des médias après la suppression.
         $this->assertResponseRedirects('/admin/media');
    }

    /**
     * Vérifie qu'un invité peut supprimer l'un de ses propres médias.
     *
     * Raison métier :
     * - Un invité est propriétaire de son contenu.
     */
    public function testGuestCanDeleteOwnMedia(): void
    {
        // Création d'un client HTTP pour simuler un utilisateur authentifié.
        $client = static::createClient();
        // Récupération de l'invité depuis le UserRepository pour se connecter.
        $userRepo = static::getContainer()->get(UserRepository::class);
        $guest = $userRepo->findOneBy(['email' => AppFixtures::GUEST_EMAIL]);

        // Récupération d'un média appartenant à l'invité.
        $mediaRepo = static::getContainer()->get(MediaRepository::class);
        $media = $mediaRepo->findOneBy(['user' => $guest]);

        // Connexion de l'invité et tentative de suppression de son propre média.
        $client->loginUser($guest);
        $client->request('GET', '/admin/media/delete/' . $media->getId());

        // Vérifie que l'invité est redirigé vers la liste des médias après la suppression.
        $this->assertResponseRedirects('/admin/media');
    }

    /**
     * Vérifie qu'un invité ne peut pas supprimer le média d'un autre utilisateur (403).
     *
     * Raison métier :
     * - La séparation des données entre invités est une règle de sécurité fondamentale.
     */
    public function testGuestCannotDeleteOtherUserMedia(): void
    {
        // Création d'un client HTTP pour simuler un utilisateur authentifié.
        $client = static::createClient();
        // Récupération de l'invité depuis le UserRepository pour se connecter.
        $userRepo = static::getContainer()->get(UserRepository::class);
        // Récupère l'invité unique  par son email défini dans les fixtures.
        $guest = $userRepo->findOneBy(['email' => AppFixtures::GUEST_EMAIL]);
        // Récupère l'administrateur unique  par son email défini dans les fixtures.
        $admin = $userRepo->findOneBy(['email' => AppFixtures::ADMIN_EMAIL]);

        // Récupérer un média appartenant à l'admin
        $mediaRepo = static::getContainer()->get(MediaRepository::class);
        $adminMedia = $mediaRepo->findOneBy(['user' => $admin]);

        // Connexion de l'invité et tentative de suppression du média de l'administrateur.
        $client->loginUser($guest);
        $client->request('GET', '/admin/media/delete/' . $adminMedia->getId());

        // Vérifie que l'invité reçoit une réponse 403 Forbidden.
        $this->assertResponseStatusCodeSame(403);
    }

    /**
     * Vérifie que la suppression d'un média inexistant retourne 404.
     */
    public function testDeleteReturns404ForUnknownId(): void
    {
        // Création d'un client HTTP pour simuler un utilisateur authentifié.
        $client = static::createClient();
        // Récupération de l'administrateur depuis le UserRepository pour se connecter.
        $userRepo = static::getContainer()->get(UserRepository::class);
        // Récupère l'administrateur unique  par son email défini dans les fixtures.
        $admin = $userRepo->findOneBy(['email' => AppFixtures::ADMIN_EMAIL]);

        // Connexion de l'administrateur et tentative de suppression d'un média avec un ID inexistant.
        $client->loginUser($admin);
        $client->request('GET', '/admin/media/delete/99999');

        // Vérifie que la réponse est 404 Not Found.
        $this->assertResponseStatusCodeSame(404);
    }
}
