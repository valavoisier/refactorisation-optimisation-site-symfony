<?php

namespace App\Tests\Unit\Entity;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        // Initialise une nouvelle instance de User avant chaque test
        $this->user = new User();
    }

    /**
     * Vérifie que la valeur par défaut du champ "blocked" est false.
     *
     * Raison métier :
     * - Un nouvel invité ne doit pas être bloqué par défaut.
     * - Ce comportement impacte directement la sécurité et l'accès au site.
     */
    public function testBlockedDefaultIsFalse(): void
    {
        $this->assertFalse($this->user->isBlocked());
    }

    /**
     * Vérifie que le rôle "ROLE_USER" est toujours présent dans la liste des rôles même si aucun rôle n'est défini.
     *
     * Raison métier :
     * - Tous les utilisateurs doivent avoir au moins le rôle "ROLE_USER".
     * - Ce rôle garantit un accès minimal aux fonctionnalités du site.
     * - La méthode getRoles() ajoute automatiquement ce rôle.
     */
    public function testRolesAlwaysContainRoleUser(): void
    {
        $this->user->setRoles([]);
        $this->assertContains('ROLE_USER', $this->user->getRoles());
    }

    /**
     * Vérifie que l'ajout d'un rôle personnalisé (ex : ROLE_ADMIN)
     * conserve ce rôle tout en ajoutant automatiquement ROLE_USER.
     *
     * Raison métier :
     * - Un administrateur doit conserver ROLE_ADMIN.
     * - Mais il doit aussi hériter de ROLE_USER pour les accès communs.
     */
    public function testRolesGetterSetter(): void
    {
        $this->user->setRoles(['ROLE_ADMIN']);
        // Le rôle admin doit être présent
        $this->assertContains('ROLE_ADMIN', $this->user->getRoles());
        // ROLE_USER doit toujours être ajouté automatiquement
        $this->assertContains('ROLE_USER', $this->user->getRoles());
    }

    /**
     * Vérifie que eraseCredentials() ne modifie pas le mot de passe.
     *
     * Raison métier :
     * - Symfony impose cette méthode pour effacer d'éventuelles données sensibles.
     * - Dans ce projet, elle ne doit rien faire (comportement volontaire).
     * - Ce test garantit que ce comportement reste inchangé.
     */
    public function testEraseCredentialsDoesNothing(): void
    {
        $this->user->setPassword('secret');
        $this->user->eraseCredentials();
        // Le mot de passe ne doit pas être effacé
        $this->assertSame('secret', $this->user->getPassword());
    }
}
