<?php

namespace App\Controller\Admin;

use App\Entity\Album;
use App\Form\AlbumType;
use App\Repository\AlbumRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Contrôleur d'administration dédié à la gestion des albums.
 *
 * Rôle :
 * - Permet à l'administrateur de gérer les albums visibles dans le portfolio.
 * - Toutes les actions sont strictement réservées à l'espace admin.
 *
 * Actions disponibles :
 * - index()  : liste tous les albums existants.
 * - add()    : crée un nouvel album via un formulaire.
 * - update() : modifie un album existant.
 * - delete() : supprime un album (ainsi que ses médias via cascade).
 */
class AlbumController extends AbstractController
{
    /**
     * Route pour afficher la liste des albums de l'administrateur
     */
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin/album', name: 'admin_album_index')]
    public function index(AlbumRepository $albumRepository): Response
    {
        // Récupération de tous les albums depuis le repository Album
        $albums = $albumRepository->findAll();

        // Affichage de la page d'administration des albums avec les données récupérées
        return $this->render('admin/album/index.html.twig', ['albums' => $albums]);
    }

    /**
     * Route pour ajouter un nouvel album
     */
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin/album/add', name: 'admin_album_add')]
    public function add(Request $request, EntityManagerInterface $em): Response
    {
        // Création d'une nouvelle instance d'Album (objet vide)
        $album = new Album();
        // Création du formulaire lié à l'entité Album
        $form = $this->createForm(AlbumType::class, $album);
        // Hydrate l'objet Album avec les données envoyées (si POST)
        $form->handleRequest($request);

        // Vérifie si le formulaire est soumis et valide
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($album);// Prépare l'album à être enregistré en base de données
            $em->flush();// Enregistre l'album en base de données

            // Redirige vers la liste des albums après l'ajout réussi
            return $this->redirectToRoute('admin_album_index');
        }
        // Affiche le formulaire d'ajout d'album 
        return $this->render('admin/album/add.html.twig', ['form' => $form]);
    }

    /**
     * Route pour mettre à jour un album existant
     */
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin/album/update/{id}', name: 'admin_album_update')]
    public function update(Request $request, int $id, AlbumRepository $albumRepository, EntityManagerInterface $em): Response
    {
        // Récupération de l'album à modifier par son ID
        $album = $albumRepository->find($id);
        // Formulaire pré-rempli avec les données de l'album
        $form = $this->createForm(AlbumType::class, $album);
        // Hydrate l'entité avec les nouvelles données envoyées
        $form->handleRequest($request);

        // Si le formulaire est soumis et valide → mise à jour
        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();// Enregistre les modifications en base de données

            // Redirige vers la liste des albums après la mise à jour réussie
            return $this->redirectToRoute('admin_album_index');
        }

        return $this->render('admin/album/update.html.twig', ['form' => $form]);
    }

    /**
     * Route pour supprimer un album existant
     */
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin/album/delete/{id}', name: 'admin_album_delete')]
    public function delete(int $id, AlbumRepository $albumRepository, EntityManagerInterface $em): Response
    {
        // Récupération de l'album à supprimer par son ID
        $album = $albumRepository->find($id);
        // Suppression de l'album (les médias liés seront supprimés automatiquement grâce au cascade remove définis dans l'entité)
        $em->remove($album);
        $em->flush();// Enregistre la suppression en base de données

        // Redirige vers la liste des albums après la suppression réussie
        return $this->redirectToRoute('admin_album_index');
    }
}