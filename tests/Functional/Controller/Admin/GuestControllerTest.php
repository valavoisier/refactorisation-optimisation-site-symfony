<?php

namespace App\Tests\Functional\Controller\Admin;

use App\DataFixtures\AppFixtures;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * TESTS FONCTIONNELS — GuestController (espace admin)
 * ==================================================
 *
 * Objectif :
 * ----------
 * Vérifier le comportement des actions d’administration des invités :
 *
 *   - Contrôle d'accès à /admin/guest (anonyme, invité, admin)
 *   - Affichage de la liste des invités
 *   - Création d’un invité via le formulaire
 *   - Bascule de l’état "bloqué / non bloqué" (toggle)
 *   - Suppression d’un invité
 *   - Gestion des IDs inexistants (404 sur toggle/delete)
 *
 * Importance métier :
 * -------------------
 * - La gestion des invités est strictement réservée à l’administrateur.
 * - L’admin doit pouvoir visualiser, créer, bloquer/débloquer et supprimer des invités.
 * - Les erreurs (ID inexistant) doivent être gérées proprement par une 404.
 */
class GuestControllerTest extends WebTestCase
{
    // ------------------------------------------------------------------ //
    //  Contrôle d'accès                                                   //
    // ------------------------------------------------------------------ //

    /**
     * Un visiteur anonyme sur /admin/guest doit être redirigé vers /login.
     *
     * Raison métier :
     * - La gestion des invités est strictement réservée à l'administrateur.
     */
    public function testIndexRedirectsAnonymousUser(): void
    {
        // Client non authentifié (visiteur anonyme)
        $client = static::createClient();
        // On tente d'accéder directement à la page d'administration des invités
        $client->request('GET', '/admin/guest');
        // L'utilisateur doit être redirigé vers la page de login
        $this->assertResponseRedirects('/login');
    }

    /**
     * Un utilisateur ROLE_USER ne doit pas accéder à /admin/guest (403).
     *
     * Raison métier :
     * - Un invité ne peut pas gérer d'autres invités.
     */
    public function testIndexForbidsGuestUser(): void
    {
        $client = static::createClient();
        // Récupération un utilisateur invité depuis les fixtures
        $userRepo = static::getContainer()->get(UserRepository::class);        
        $guest = $userRepo->findOneBy(['email' => AppFixtures::GUEST_EMAIL]);

        // Connexion de l'utilisateur invité
        $client->loginUser($guest);
        // Tentative d'accès à la page d'administration des invités
        $client->request('GET', '/admin/guest');

        // L'accès doit être refusé avec un statut 403 Forbidden
        $this->assertResponseStatusCodeSame(403);
    }

    /**
     * L'admin peut accéder à la liste des invités (200).
     */
    public function testIndexIsAccessibleByAdmin(): void
    {
        $client = static::createClient();
        // Récupération de l'administrateur depuis les fixtures
        $userRepo = static::getContainer()->get(UserRepository::class);
        $admin = $userRepo->findOneBy(['email' => AppFixtures::ADMIN_EMAIL]);

        // Connexion de l'administrateur
        $client->loginUser($admin);
        // Accès à la page d'administration des invités
        $client->request('GET', '/admin/guest');

        // La page doit se charger avec succès (200 OK)
        $this->assertResponseIsSuccessful();
    }

    // ------------------------------------------------------------------ //
    //  Listing des invités                                                   //
    // ------------------------------------------------------------------ //

    /**
     * La page index affiche bien les invités des fixtures (actif + bloqué).
     *
     * Raison métier :
     * - L'admin doit voir tous les invités pour pouvoir les gérer.
     */
    public function testIndexDisplaysGuests(): void
    {
        $client = static::createClient();
        // Récupération de l'administrateur depuis les fixtures
        $userRepo = static::getContainer()->get(UserRepository::class);
        $admin = $userRepo->findOneBy(['email' => AppFixtures::ADMIN_EMAIL]);

        // Connexion de l'administrateur
        $client->loginUser($admin);
        // Accès à la page d'administration avec la liste de l'ensemble des invités
        $client->request('GET', '/admin/guest');

        // La page doit se charger avec succès (200 OK)
        $this->assertResponseIsSuccessful();
        // La liste doit être rendue sous forme de tableau HTML
        $this->assertSelectorExists('table');
    }

    // ------------------------------------------------------------------ //
    //  Ajout                                                               //
    // ------------------------------------------------------------------ //

    /**
     * Un GET sur /admin/guest/add affiche le formulaire (200).
     */
    public function testAddPageLoads(): void
    {
        $client = static::createClient();
        // Récupération de l'administrateur depuis les fixtures
        $userRepo = static::getContainer()->get(UserRepository::class);
        $admin = $userRepo->findOneBy(['email' => AppFixtures::ADMIN_EMAIL]);

        // Connexion de l'administrateur
        $client->loginUser($admin);
        // Accès à la page de création d'un invité
        $client->request('GET', '/admin/guest/add');

        // La page doit se charger avec succès (200 OK)
        $this->assertResponseIsSuccessful();
        // Le formulaire de création doit être présent dans la page
        $this->assertSelectorExists('form');
    }

    /**
     * La soumission d'un formulaire valide crée l'invité et redirige vers la liste.
     *
     * Raison métier :
     * - L'admin doit pouvoir créer un nouvel invité depuis l'interface.
     */
    public function testAddValidFormCreatesGuest(): void
    {
        $client = static::createClient();
        // Récupération de l'administrateur depuis les fixtures
        $userRepo = static::getContainer()->get(UserRepository::class);
        $admin = $userRepo->findOneBy(['email' => AppFixtures::ADMIN_EMAIL]);

        // Connexion de l'administrateur
        $client->loginUser($admin);
        // Affichage du formulaire de création d'un invité
        $crawler = $client->request('GET', '/admin/guest/add');

        // Remplissage du formulaire avec des données valides
        $form = $crawler->selectButton('Créer l\'invité')->form([
            'guest[name]'          => 'Nouveau Invité',
            'guest[email]'         => 'nouveau@example.com',
            'guest[plainPassword]' => 'Secure1234!@#',
        ]);
        // Soumission du formulaire
        $client->submit($form);

        // Après création, l'admin est redirigé vers la liste des invités
        $this->assertResponseRedirects('/admin/guest');
    }

    // ------------------------------------------------------------------ //
    //  Toggle (bloquer / débloquer)                                        //
    // ------------------------------------------------------------------ //

    /**
     * Le toggle inverse l'état blocked d'un invité et redirige vers la liste.
     *
     * Raison métier :
     * - Bloquer un invité lui interdit immédiatement l'accès au site.
     */
    public function testToggleInvertsBlockedState(): void
    {
        $client = static::createClient();
        // Récupération de l'administrateur et d'un invité actif depuis les fixtures
        $userRepo = static::getContainer()->get(UserRepository::class);
        $admin = $userRepo->findOneBy(['email' => AppFixtures::ADMIN_EMAIL]);
        $guest = $userRepo->findOneBy(['email' => AppFixtures::GUEST_EMAIL]);

        // On mémorise l'état initial de l'invité (bloqué ou non)
        $wasBlocked = $guest->isBlocked(); // false en fixtures

        // Connexion de l'administrateur
        $client->loginUser($admin);
        // Appel de l'action toggle sur l'invité
        $client->request('GET', '/admin/guest/toggle/' . $guest->getId());

        // Après le toggle, l'admin est redirigé vers la liste des invités
        $this->assertResponseRedirects('/admin/guest');

        // On vide le cache Doctrine pour relire l'état depuis la base
        $em = static::getContainer()->get('doctrine')->getManager();
        $em->clear();
        // On recharge l'utilisateur pour vérifier que l'état blocked a bien été inversé
        $refreshed = $userRepo->find($guest->getId());
        $this->assertSame(!$wasBlocked, $refreshed->isBlocked());
    }

    /**
     * Toggle sur un ID inexistant retourne 404.
     */
    public function testToggleReturns404ForUnknownId(): void
    {
        $client = static::createClient();
        // Récupération de l'administrateur depuis les fixtures
        $userRepo = static::getContainer()->get(UserRepository::class);
        $admin = $userRepo->findOneBy(['email' => AppFixtures::ADMIN_EMAIL]);

        // Connexion de l'administrateur
        $client->loginUser($admin);
        // Appel de toggle sur un ID invité inexistant
        $client->request('GET', '/admin/guest/toggle/99999');

        // Le contrôleur doit répondre par une 404
        $this->assertResponseStatusCodeSame(404);
    }

    // ------------------------------------------------------------------ //
    //  Suppression                                                         //
    // ------------------------------------------------------------------ //

    /**
     * La suppression d'un invité redirige vers la liste et retire l'invité de la base.
     *
     * Raison métier :
     * - L'admin doit pouvoir supprimer un compte invité et toutes ses données (cascade).
     */
    public function testDeleteRemovesGuest(): void
    {
        $client = static::createClient();
        // Récupération de l'administrateur et d'un invité bloqué depuis les fixtures
        $userRepo = static::getContainer()->get(UserRepository::class);
        $admin = $userRepo->findOneBy(['email' => AppFixtures::ADMIN_EMAIL]);
        $blocked = $userRepo->findOneBy(['email' => AppFixtures::BLOCKED_EMAIL]);
        $blockedId = $blocked->getId();

        // Connexion de l'administrateur
        $client->loginUser($admin);
        // Appel de la route de suppression sur l'invité bloqué
        $client->request('GET', '/admin/guest/delete/' . $blockedId);

        // L'action redirige vers la liste des invités
        $this->assertResponseRedirects('/admin/guest');

        // // On vide le cache Doctrine pour relire l'état depuis la base et vérifier que l'invité n'existe plus
        $em = static::getContainer()->get('doctrine')->getManager();
        $em->clear();
        // On tente de retrouver l'invité supprimé, qui doit être introuvable (null)
        $deleted = $userRepo->find($blockedId);
        $this->assertNull($deleted);
    }

    /**
     * Delete sur un ID inexistant retourne 404.
     */
    public function testDeleteReturns404ForUnknownId(): void
    {
        $client = static::createClient();
        // Récupération de l'administrateur depuis les fixtures
        $userRepo = static::getContainer()->get(UserRepository::class);
        $admin = $userRepo->findOneBy(['email' => AppFixtures::ADMIN_EMAIL]);

        // Connexion de l'administrateur
        $client->loginUser($admin);
        // Appel de la route de suppression sur un ID inexistant
        $client->request('GET', '/admin/guest/delete/99999');

        // Le contrôleur doit répondre par une 404
        $this->assertResponseStatusCodeSame(404);
    }
}
