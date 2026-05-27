<?php

namespace App\Tests\Functional\Controller;

use App\DataFixtures\AppFixtures;
use App\Tests\Functional\FunctionalTestCase;

/**
 * TESTS FONCTIONNELS — HomeController (front office public)
 * =========================================================.
 *
 * Objectif :
 * ----------
 * Vérifier le comportement du front office public, accessible sans authentification :
 *
 *   - Page d'accueil (/)
 *   - Page À propos (/about)
 *   - Liste publique des invités (/guests)
 *       • invité actif visible
 *       • invité bloqué invisible
 *   - Profil d’un invité (/guest/{id})
 *       • 200 si invité actif
 *       • 404 si invité bloqué
 *       • 404 si ID inexistant
 *   - Portfolio général (/portfolio)
 *   - Portfolio filtré par album (/portfolio/{id})
 *
 * Importance métier :
 * -------------------
 * - Le front office constitue la partie publique du site, accessible à tous.
 * - Les invités actifs doivent être visibles, les invités bloqués doivent rester invisibles.
 * - Les profils doivent respecter les règles métier : actif → accessible, bloqué → 404.
 * - Le portfolio est la vitrine principale : il doit toujours être accessible.
 * - Les pages statiques doivent fonctionner sans erreur (accueil, à propos).
 */
class HomeControllerTest extends FunctionalTestCase
{
    // ------------------------------------------------------------------ //
    //  Pages statiques                                                     //
    // ------------------------------------------------------------------ //

    /**
     * Vérifie que la page d'accueil est accessible (HTTP 200).
     */
    public function testHomePageLoads(): void
    {
        // Création d'un client HTTP pour simuler un visiteur non authentifié
        $client = static::createClient();
        // Envoi d'une requête GET à la page d'accueil
        $client->request('GET', '/');

        // Vérifie que la réponse est un succès (HTTP 200)
        $this->assertResponseIsSuccessful();
    }

    /**
     * Vérifie que la page "À propos" est accessible (HTTP 200).
     */
    public function testAboutPageLoads(): void
    {
        // Création d'un client HTTP pour simuler un visiteur non authentifié
        $client = static::createClient();
        // Envoi d'une requête GET à la page "À propos"
        $client->request('GET', '/about');

        // Vérifie que la réponse est un succès (HTTP 200)
        $this->assertResponseIsSuccessful();
    }

    // ------------------------------------------------------------------ //
    //  Liste des invités (/guests)                                         //
    // ------------------------------------------------------------------ //

    /**
     * Vérifie que la page listant les invités est accessible (HTTP 200).
     */
    public function testGuestsPageLoads(): void
    {
        // Création d'un client HTTP pour simuler un visiteur non authentifié
        $client = static::createClient();
        // Envoi d'une requête GET à la page listant les invités
        $client->request('GET', '/guests');

        // Vérifie que la page charge correctement - succès (HTTP 200)
        $this->assertResponseIsSuccessful();
    }

    /**
     * Vérifie que l'invité actif des fixtures apparaît dans la liste publique.
     *
     * Raison métier :
     * - Les visiteurs doivent pouvoir découvrir les invités actifs.
     */
    public function testGuestsPageShowsActiveGuest(): void
    {
        // Création d'un client HTTP pour simuler un visiteur non authentifié
        $client = static::createClient();
        // Envoi d'une requête GET à la page listant les invités
        $client->request('GET', '/guests');

        // Vérifie que la page charge correctement - succès (HTTP 200)
        $this->assertResponseIsSuccessful();
        // Vérifie que l'invité actif des fixtures est présent dans la page
        $this->assertSelectorTextContains('body', 'Invité Actif');
    }

    /**
     * Vérifie que l'invité bloqué n'apparaît PAS dans la liste publique.
     *
     * Raison métier :
     * - Un compte bloqué ne doit pas être visible publiquement.
     */
    public function testGuestsPageHidesBlockedGuest(): void
    {
        // Création d'un client HTTP pour simuler un visiteur non authentifié
        $client = static::createClient();
        // Envoi d'une requête GET à la page listant les invités
        $client->request('GET', '/guests');

        // Vérifie que la page charge correctement - succès (HTTP 200)
        $this->assertResponseIsSuccessful();
        // Vérifie que l'invité bloqué n'apparaît pas via son attribut data-email
        $this->assertSelectorNotExists('[data-email="'.AppFixtures::BLOCKED_EMAIL.'"]');
        // Vérifie également que son nom n'apparaît pas dans le contenu HTML
        $content = $client->getResponse()->getContent();
        $this->assertStringNotContainsString('Invité Bloqué', $content);
    }

    // ------------------------------------------------------------------ //
    //  Profil d'un invité (/guest/{id})                                    //
    // ------------------------------------------------------------------ //

    /**
     * Vérifie que la page d'un invité actif est accessible (HTTP 200).
     *
     * Raison métier :
     * - Les visiteurs doivent pouvoir consulter le profil d'un invité actif.
     */
    public function testGuestProfilePageLoads(): void
    {
        // Création d'un client HTTP pour simuler un visiteur non authentifié
        $client = static::createClient();
        // Récupération du repository User pour obtenir l'invité actif depuis les fixtures
        $guest = $this->getActiveGuest();

        // Envoi d'une requête GET à la page de profil de l'invité actif
        $client->request('GET', '/guest/'.$guest->getId());

        // Vérifie que la page charge correctement - succès (HTTP 200)
        $this->assertResponseIsSuccessful();
    }

    /**
     * Vérifie que la page d'un invité bloqué retourne 404.
     *
     * Raison métier :
     * - Un compte bloqué ne doit pas être accessible en front office.
     */
    public function testGuestProfilePageReturns404ForBlockedGuest(): void
    {
        // Création d'un client HTTP pour simuler un visiteur non authentifié
        $client = static::createClient();
        // Récupération du repository User pour obtenir l'invité bloqué depuis les fixtures
        $blocked = $this->getBlockedGuest();

        // Envoi d'une requête GET à la page de profil de l'invité bloqué
        $client->request('GET', '/guest/'.$blocked->getId());

        // Vérifie que la page retourne 404
        $this->assertResponseStatusCodeSame(404);
    }

    /**
     * Vérifie qu'un ID inexistant retourne une erreur 404.
     */
    public function testGuestProfilePageReturns404ForUnknownId(): void
    {
        // Création d'un client HTTP pour simuler un visiteur non authentifié
        $client = static::createClient();
        // Envoi d'une requête GET à la page de profil d'un ID inexistant
        $client->request('GET', '/guest/99999');

        // Vérifie que la page retourne 404
        $this->assertResponseStatusCodeSame(404);
    }

    // ------------------------------------------------------------------ //
    //  Portfolio (/portfolio)                                              //
    // ------------------------------------------------------------------ //

    /**
     * Vérifie que la page du portfolio général est accessible (HTTP 200).
     *
     * Raison métier :
     * - C'est la vitrine principale des médias d'Ina.
     */
    public function testPortfolioPageLoads(): void
    {
        // Création d'un client HTTP pour simuler un visiteur non authentifié
        $client = static::createClient();
        // Envoi d'une requête GET à la page du portfolio général
        $client->request('GET', '/portfolio');

        // Vérifie que la page charge correctement - succès (HTTP 200)
        $this->assertResponseIsSuccessful();
    }

    /**
     * Vérifie que le portfolio filtré par un album existant est accessible (HTTP 200).
     *
     * Raison métier :
     * - Les visiteurs peuvent filtrer et consulter le portfolio par album.
     */
    public function testPortfolioPageWithAlbumFilter(): void
    {
        // Création d'un client HTTP pour simuler un visiteur non authentifié
        $client = static::createClient();

        // Récupérer l'ID du premier album créé par les fixtures
        $albumRepo = static::getContainer()->get('doctrine')->getRepository(\App\Entity\Album::class);
        $album = $albumRepo->findOneBy([]);

        // Envoi d'une requête GET à la page du portfolio filtré par l'ID de l'album
        $client->request('GET', '/portfolio/'.$album->getId());

        // Vérifie que la page charge correctement - succès (HTTP 200)
        $this->assertResponseIsSuccessful();
    }
}
