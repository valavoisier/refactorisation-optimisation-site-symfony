<?php

namespace App\Controller;

use App\Repository\AlbumRepository;
use App\Repository\MediaRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
/**
 * Contrôleur principal pour les pages: 
 * - accueil
 * - présentation des invités
 * - portfolio
 * - page "À propos".
 */
class HomeController extends AbstractController
{
    // Route pour la page d'accueil
    #[Route('/', name: 'home')]
    public function home(): Response
    {
        return $this->render('front/home.html.twig');
    }

    // Route pour la liste des invités
    #[Route('/guests', name: 'guests')]
    public function guests(UserRepository $userRepository): Response
    {
        // Récupération des invités actifs (non bloqués) depuis le repository User
        $guests = $userRepository->findActiveGuests();
        // Affichage de la page des invités avec les données récupérées
        return $this->render('front/guests.html.twig', [
            'guests' => $guests
        ]);
    }

    // Route pour afficher les détails d'un invité spécifique
    #[Route('/guest/{id}', name: 'guest')]
    public function guest(int $id, UserRepository $userRepository): Response
    {
        // Récupération de l'invité par son ID
        $guest = $userRepository->find($id);

        // Si l'invité n'existe pas ou est bloqué, on affiche une page d'erreur 404
        if (!$guest || $guest->isBlocked()) {
            throw $this->createNotFoundException('Invité introuvable.');
        }

        // Affichage de la page de détails de l'invité avec les données récupérées
        return $this->render('front/guest.html.twig', [
            'guest' => $guest
        ]);
    }

    // Page portfolio : affiche le travail d'Ina l'admin
    #[Route('/portfolio/{id}', name: 'portfolio', defaults: ['id' => null])]
    public function portfolio(?int $id, AlbumRepository $albumRepository, MediaRepository $mediaRepository, UserRepository $userRepository): Response
    {
        // Tous les albums créés par Ina (seule l'admin peut créer des albums)
        $albums = $albumRepository->findAll();
        // Album sélectionné (ou null si aucun id fourni)
        $album = $id ? $albumRepository->find($id) : null;
        // Récupère Ina (admin) pour afficher uniquement son travail
        $user = $userRepository->findAdmin();

        // Si un album est sélectionné → médias de cet album (Ina)
        // Sinon → tous les médias d'Ina
        $medias = $album
            ? $mediaRepository->findByAlbum($album)
            : $mediaRepository->findByUser($user);
        // Envoie les données à la vue
        return $this->render('front/portfolio.html.twig', [
            'albums' => $albums,
            'album' => $album,
            'medias' => $medias
        ]);
    }

    // Route pour la page "À propos"
    #[Route('/about', name: 'about')]
    public function about(): Response
    {
        return $this->render('front/about.html.twig');
    }
}