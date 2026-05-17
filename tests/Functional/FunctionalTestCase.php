<?php

namespace App\Tests\Functional;

use App\DataFixtures\AppFixtures;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Classe de base abstraite pour tous les tests fonctionnels.
*
 * Rôle :
 * - Fournir les utilisateurs des fixtures (admin, invité actif, invité bloqué)
 * - Fournir un accès simplifié au UserRepository et à l’EntityManager
 * - Créer des clients HTTP déjà authentifiés
 * - Centraliser les helpers pour éviter les doublons dans les tests fonctionnels
 *
 * Méthodes disponibles :
 * - getAdmin()           : retourne l'utilisateur ROLE_ADMIN des fixtures.
 * - getActiveGuest()     : retourne l'invité actif (non bloqué) des fixtures.
 * - getBlockedGuest()    : retourne l'invité bloqué des fixtures.
 * - getUserRepository()  : retourne le UserRepository depuis le conteneur.
 * - getEntityManager()   : retourne l'EntityManager depuis le conteneur.
 * - createAdminClient()  : crée un client HTTP et authentifie immédiatement l'administrateur.
 * - createGuestClient()  : crée un client HTTP et authentifie immédiatement l'invité actif.
 * 
 * Note :
 * setUp() n’est pas utilisé car il exécuterait du code avant createClient(),
 * provoquant un double démarrage du kernel et cassant l’isolation des tests.
 * - Les helpers (getAdmin(), createAdminClient(), etc.) sont donc appelés directement
 *   dans les tests, après createClient(), ce qui respecte l’ordre correct de WebTestCase.
 */

abstract class FunctionalTestCase extends WebTestCase
{
    /**
     * Retourne l'administrateur défini dans les fixtures (ROLE_ADMIN).      
     */
    protected function getAdmin(): User
    {
        // Récupère l'administrateur depuis les fixtures en utilisant son email unique
        return static::getContainer()->get(UserRepository::class)
            ->findOneBy(['email' => AppFixtures::ADMIN_EMAIL]);
    }

    /**
     * Retourne l'invité actif (non bloqué) défini dans les fixtures.     
     */
    protected function getActiveGuest(): User
    {
        // Récupère l'invité actif depuis les fixtures en utilisant son email unique
        return static::getContainer()->get(UserRepository::class)
            ->findOneBy(['email' => AppFixtures::GUEST_EMAIL]);
    }

    /**
     * Retourne l'invité bloqué défini dans les fixtures.    
     */
    protected function getBlockedGuest(): User
    {
        // Récupère l'invité bloqué depuis les fixtures en utilisant son email unique
        return static::getContainer()->get(UserRepository::class)
            ->findOneBy(['email' => AppFixtures::BLOCKED_EMAIL]);
    }

    /**
     * Retourne le UserRepository.
     *
     * Utile pour recharger une entité après une action HTTP (em->clear()).
     */
    protected function getUserRepository(): UserRepository
    {
        // Récupère le UserRepository depuis le conteneur de services pour les opérations directes en base
        return static::getContainer()->get(UserRepository::class);
    }

    /**
     * Retourne l'EntityManager.
     *
     * Utile pour manipuler directement des entités dans certains tests
     * ou vider le cache Doctrine (em->clear()).
     */
    protected function getEntityManager(): EntityManagerInterface
    {
        // Récupère l'EntityManager depuis le conteneur de services pour les opérations directes en base
        return static::getContainer()->get('doctrine')->getManager();
    }

    /**
     * Crée un client HTTP authentifié en tant qu’administrateur.
     *
     * Remplace le doublon : createClient() + getAdmin() + loginUser()
     */
    protected function createAdminClient(): KernelBrowser
    {
        // Création d'un client HTTP pour simuler un visiteur authentifié
        $client = static::createClient();
        // Authentifie le client avec l'administrateur
        $client->loginUser($this->getAdmin());
        // Retourne le client déjà authentifié pour les tests fonctionnels
        return $client;
    }

    /**
     * Crée un client HTTP authentifié en tant qu’invité actif.
     *
     * Remplace le doublon : createClient() + getActiveGuest() + loginUser()
     */
    protected function createGuestClient(): KernelBrowser
    {
        $client = static::createClient();
        // Authentifie le client avec l'invité actif
        $client->loginUser($this->getActiveGuest());
        // Retourne le client déjà authentifié pour les tests fonctionnels
        return $client;
    }
}
