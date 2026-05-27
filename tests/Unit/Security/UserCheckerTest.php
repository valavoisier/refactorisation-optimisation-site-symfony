<?php

namespace App\Tests\Unit\Security;

use App\Entity\User;
use App\Security\UserChecker;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserInterface;

class UserCheckerTest extends TestCase
{
    private UserChecker $checker;

    protected function setUp(): void
    {
        // Initialise une nouvelle instance de UserChecker avant chaque test
        $this->checker = new UserChecker();
    }

    /**
     * Un utilisateur bloqué ne doit pas pouvoir se connecter.
     * - Vérifie que checkPreAuth() bloque un utilisateur marqué comme "blocked"
     *   via l'exception CustomUserMessageAccountStatusException
     *
     * Raison métier :
     * - C'est la protection centrale contre les comptes révoqués.
     * - L'exception doit être levée AVANT la vérification du mot de passe.
     */
    public function testCheckPreAuthThrowsExceptionForBlockedUser(): void
    {
        $user = new User();
        $user->setBlocked(true);

        // On s'attend à ce qu'une exception soit levée pour un utilisateur bloqué
        $this->expectException(CustomUserMessageAccountStatusException::class);

        $this->checker->checkPreAuth($user);
    }

    /**
     * Vérifie qu'un utilisateur actif (non bloqué) passe la vérification checkPreAuth sans erreur.
     *
     * Raison métier :
     * - Aucune exception ne doit être levée pour un compte valide.
     */
    public function testCheckPreAuthAllowsActiveUser(): void
    {
        $user = new User();
        $user->setBlocked(false);

        // Aucune exception attendue
        $this->expectNotToPerformAssertions();
        $this->checker->checkPreAuth($user);
    }

    /**
     * Vérifie que les objets UserInterface non issus de l'entité User
     *   sont ignorés silencieusement par checkPreAuth (guard clause)
     *
     * Raison métier :
     * - Guard clause de sécurité : évite une erreur si un autre UserInterface est utilisé.
     */
    public function testCheckPreAuthIgnoresNonUserObject(): void
    {
        $otherUser = $this->createMock(UserInterface::class);

        // Aucune exception attendue
        $this->expectNotToPerformAssertions();
        $this->checker->checkPreAuth($otherUser);
    }

    /**
     * Vérifie que checkPostAuth() ne réalise aucune action post-authentification
     * checkPostAuth ne doit jamais lever d'exception.
     *
     * Raison métier :
     * - Symfony appelle cette méthode après authentification réussie.
     * - Dans cette application, aucune vérification post-auth n'est requise.
     * - Le comportement attendu est : ne rien faire.
     */
    public function testCheckPostAuthDoesNothing(): void
    {
        $user = new User();

        // Aucune exception attendue
        $this->expectNotToPerformAssertions();
        $this->checker->checkPostAuth($user);
    }
}
