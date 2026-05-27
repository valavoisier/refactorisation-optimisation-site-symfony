<?php

namespace App\Tests\Functional\Repository;

use App\DataFixtures\AppFixtures;
use App\Repository\UserRepository;
use App\Tests\Functional\FunctionalTestCase;

/**
 * TESTS FONCTIONNELS — UserRepository
 * ===================================.
 *
 * Objectif :
 * ----------
 * Vérifier le bon fonctionnement des trois méthodes personnalisées du
 * UserRepository, essentielles au fonctionnement du Front Office et du Back Office :
 *
 *   - findAdmin()        → retourne l’unique administrateur
 *   - findGuests()       → retourne tous les invités (admin exclu)
 *   - findActiveGuests() → retourne uniquement les invités non bloqués
 *
 * Importance métier :
 * -------------------
 * - L’admin est indispensable pour accéder au Back Office.
 * - Les invités actifs alimentent la page publique /guests.
 * - Les invités bloqués doivent être invisibles sur le site public.
 */
class UserRepositoryTest extends FunctionalTestCase
{
    private UserRepository $repo;

    protected function setUp(): void
    {
        // Boot le kernel pour accéder au conteneur et récupérer le UserRepository
        self::bootKernel();
        // Récupère le UserRepository depuis le conteneur de services
        $this->repo = self::getContainer()->get(UserRepository::class);
    }

    /**
     * findAdmin() doit retourner l'unique utilisateur ROLE_ADMIN des fixtures.
     *
     * Raison métier :
     * - L'admin est la seule personne autorisée à gérer les invités et les albums.
     * - Si findAdmin() retourne null, toutes les pages admin seraient inaccessibles.
     */
    public function testFindAdminReturnsAdmin(): void
    {
        // Récupère l'administrateur unique
        $admin = $this->repo->findAdmin();

        // Vérifie que l'administrateur existe et a les bonnes propriétés
        $this->assertNotNull($admin);
        // Vérifie que l'email de l'administrateur correspond à celui des fixtures
        $this->assertSame(AppFixtures::ADMIN_EMAIL, $admin->getUserIdentifier());
        // Vérifie que l'administrateur a bien le rôle ROLE_ADMIN
        $this->assertContains('ROLE_ADMIN', $admin->getRoles());
    }

    /**
     * findGuests() doit retourner tous les utilisateurs sans ROLE_ADMIN.
     *
     * Raison métier :
     * - Les fixtures contiennent 2 invités (1 actif + 1 bloqué).
     * - L'admin ne doit jamais apparaître dans cette liste.
     */
    public function testFindGuestsReturnsOnlyNonAdmins(): void
    {
        // Récupère tous les invités (sans ROLE_ADMIN)
        $guests = $this->repo->findGuests();

        // Vérifie que nous avons bien 2 invités (1 actif + 1 bloqué)
        $this->assertCount(2, $guests);
        // Vérifie que aucun invité n'a le rôle ROLE_ADMIN
        foreach ($guests as $guest) {
            // 2 invités 2 assertions
            $this->assertNotContains('ROLE_ADMIN', $guest->getRoles());
        }
    }

    /**
     * findActiveGuests() doit retourner uniquement les invités non bloqués.
     *
     * Raison métier :
     * - Seuls les invités actifs apparaissent sur la page publique /guests.
     * - Les comptes bloqués doivent être exclus du résultat.
     */
    public function testFindActiveGuestsExcludesBlockedUsers(): void
    {
        // Récupère les invités actifs (sans ROLE_ADMIN et non bloqués)
        $activeGuests = $this->repo->findActiveGuests();

        // Vérifie que nous avons bien 1 invité actif
        $this->assertCount(1, $activeGuests);
        // Vérifie que cet invité actif est bien celui des fixtures (GUEST_EMAIL) et qu'il n'est pas bloqué
        $this->assertSame(AppFixtures::GUEST_EMAIL, $activeGuests[0]->getUserIdentifier());
        // Vérifie que cet invité n'est pas bloqué
        $this->assertFalse($activeGuests[0]->isBlocked());
    }

    /**
     * findActiveGuests() ne doit pas contenir l'invité bloqué des fixtures.
     *
     * Raison métier :
     * - Vérification explicite que l'invité bloqué (BLOCKED_EMAIL) est absent.
     */
    public function testFindActiveGuestsDoesNotIncludeBlockedGuest(): void
    {
        // Récupère les invités actifs (sans ROLE_ADMIN et non bloqués)
        $activeGuests = $this->repo->findActiveGuests();

        // Vérifie que l'invité bloqué (BLOCKED_EMAIL) n'est pas présent dans la liste des invités actifs
        $emails = array_map(fn ($u) => $u->getUserIdentifier(), $activeGuests);
        // L'invité bloqué ne doit pas être dans la liste des invités actifs
        $this->assertNotContains(AppFixtures::BLOCKED_EMAIL, $emails);
    }
}
