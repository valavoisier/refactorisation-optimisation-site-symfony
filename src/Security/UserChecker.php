<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Classe UserChecker
 *
 * Cette classe est utilisée par Symfony pour vérifier l’état d’un compte
 * utilisateur avant et après l’authentification.
 * 
 *IMPORTANT :
 * -----------
 * Pour que ce UserChecker soit réellement utilisé par Symfony, il doit être
 * explicitement déclaré dans le firewall principal du fichier security.yaml :
 *
 * firewalls:
 *     main:
 *         user_checker: App\Security\UserChecker
 *
 * Sans cette configuration, Symfony n’appellera jamais cette classe.
 * ------------
 * 
 * Elle est automatiquement appelée par le système de sécurité :
 *  - checkPreAuth() : avant la vérification du mot de passe
 *  - checkPostAuth() : après une authentification réussie
 *
 * UserChecker permet d’empêcher la connexion d’un utilisateur
 * dont le compte est marqué comme "bloqué" dans la base de données.
 *
 * Référence documentation Symfony :
 * https://symfony.com/doc/current/security/user_checkers.html
 *
 * Fonctionnement :
 * ----------------
 * Lorsqu’un utilisateur tente de se connecter :
 *  1. Symfony charge l’utilisateur depuis la base de données.
 *  2. Symfony appelle checkPreAuth().
 *  3. Si l’utilisateur est bloqué, une exception est levée et la connexion est refusée.
 *  4. Sinon, Symfony poursuit l’authentification et crée la session.
 *
 * L’exception CustomUserMessageAccountStatusException permet d’afficher un
 * message clair à l’utilisateur sans exposer de détails techniques.
 */
class UserChecker implements UserCheckerInterface
{
    /**
     * Vérification effectuée avant l’authentification.
     *
     * Si l’utilisateur est bloqué (champ "blocked" à true),
     * la connexion est immédiatement refusée.
     */
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        if ($user->isBlocked()) {
            throw new CustomUserMessageAccountStatusException(
                'Votre compte a été bloqué. Veuillez contacter l\'administrateur.'
            );
        }
    }

    /**
 * Vérification effectuée après l’authentification.
 *
 * Cette méthode est appelée uniquement si l’utilisateur a été authentifié
 * avec succès (mot de passe valide). Symfony l’utilise pour permettre
 * l’ajout de contrôles supplémentaires après la connexion, par exemple :
 *
 *  - vérifier qu’un compte n’a pas expiré ;
 *  - vérifier des rôles ou permissions dans le Token ;
 *  - appliquer des règles métier post-authentification ;
 *  - forcer un changement de mot de passe ;
 *  - refuser l’accès même après un mot de passe correct.
 *
 * ! ATTENTION:Dans cette application, aucun contrôle post-authentification n’est pas nécessaire.
 * La méthode est donc vide, mais DOIT ÊTRE PRÉSENTE pour respecter
 * l’interface UserCheckerInterface.
 */
    public function checkPostAuth(UserInterface $user): void
    {
        // Aucune vérification nécessaire après authentification
    }
}
