<?php

namespace App\Controller;

use App\Repository\AlbumRepository;
use App\Repository\MediaRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function home(): Response
    {
        return $this->render('front/home.html.twig');
    }

    #[Route('/guests', name: 'guests')]
    public function guests(UserRepository $userRepository): Response
    {
        $guests = $userRepository->findGuests();
        return $this->render('front/guests.html.twig', [
            'guests' => $guests
        ]);
    }

    #[Route('/guest/{id}', name: 'guest')]
    public function guest(int $id, UserRepository $userRepository): Response
    {
        $guest = $userRepository->find($id);

        if (!$guest || $guest->isBlocked()) {
            throw $this->createNotFoundException('Invité introuvable.');
        }

        return $this->render('front/guest.html.twig', [
            'guest' => $guest
        ]);
    }

    #[Route('/portfolio/{id}', name: 'portfolio', defaults: ['id' => null])]
    public function portfolio(?int $id, AlbumRepository $albumRepository, MediaRepository $mediaRepository, UserRepository $userRepository): Response
    {
        $albums = $albumRepository->findAll();
        $album = $id ? $albumRepository->find($id) : null;
        $user = $userRepository->findAdmin();

        $medias = $album
            ? $mediaRepository->findByAlbum($album)
            : $mediaRepository->findByUser($user);
        return $this->render('front/portfolio.html.twig', [
            'albums' => $albums,
            'album' => $album,
            'medias' => $medias
        ]);
    }

    #[Route('/about', name: 'about')]
    public function about(): Response
    {
        return $this->render('front/about.html.twig');
    }
}