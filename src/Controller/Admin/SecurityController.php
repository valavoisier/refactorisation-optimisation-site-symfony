<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
/**
 * Contrôleur pour la gestion de la sécurité (login/logout) dans l'administration.
 */
class SecurityController extends AbstractController
{
    // Page de connexion de l'administration
    #[Route('/login', name: 'admin_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // Récupère les erreurs de connexion s'il y en a 
        $error = $authenticationUtils->getLastAuthenticationError();
        // Dernier nom d'utilisateur entré par l'utilisateur lors de la tentative de connexion
        $lastUsername = $authenticationUtils->getLastUsername();
        // Affiche la page de connexion avec les données d'erreur et le dernier nom d'utilisateur
        return $this->render('admin/login.html.twig', [
            'last_username' => $lastUsername,
            'error'         => $error,
        ]);
    }

    // Route pour la déconnexion de l'administration
    #[Route('/logout', name: 'admin_logout')]
    public function logout(): never
    {
        // Intercepté automatiquement par le firewall Symfony
        // Cette méthode ne doit jamais être exécutée directement, 
        //elle est juste une route pour que Symfony puisse gérer la déconnexion
        throw new \LogicException('Cette méthode ne doit pas être appelée directement.');
    }
}