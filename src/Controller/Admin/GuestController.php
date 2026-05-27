<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Form\GuestType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Contrôleur d'administration dédié à la gestion des invités.
 *
 * Rôle :
 * - Permet à l'administrateur de lister, créer, bloquer/débloquer et supprimer les invités.
 * - Les invités correspondent aux utilisateurs ayant le rôle ROLE_USER.
 * - Toutes les actions sont strictement réservées à l'espace admin.
 *
 * Actions disponibles :
 * - index()  : liste tous les invités (actifs ou bloqués).
 * - add()    : crée un nouvel invité avec mot de passe hashé.
 * - toggle() : active ou bloque un invité.
 * - delete() : supprime un invité (et ses médias via cascade).
 */
class GuestController extends AbstractController
{
    /**
     * Route pour afficher la liste des invités.
     */
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin/guest', name: 'admin_guest_index')]
    public function index(UserRepository $userRepository): Response
    {
        // findGuests() est une méthode personnalisée dans UserRepository
        // cette méthode récupère tous les utilisateurs avec le rôle ROLE_USER
        return $this->render('admin/guest/index.html.twig', [
            'guests' => $userRepository->findGuests(),
        ]);
    }

    /**
     * Route pour ajouter un nouvel invité.
     */
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin/guest/add', name: 'admin_guest_add')]
    public function add(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher,
    ): Response {
        // Création d'une nouvelle instance de User (invité)
        $guest = new User();
        // Création du formulaire lié à l'entité User (GuestType)
        $form = $this->createForm(GuestType::class, $guest);
        // Hydrate l'objet User avec les données envoyées (si POST)
        $form->handleRequest($request);

        // Vérifie si le formulaire est soumis et valide
        if ($form->isSubmitted() && $form->isValid()) {
            // Hash le mot de passe saisi dans plainpassword avant de le stocker en base de données
            $guest->setPassword(
                $hasher->hashPassword($guest, $form->get('plainPassword')->getData())
            );
            $guest->setRoles(['ROLE_USER']); // Attribution du rôle de base ROLE_USER à l'invité
            $em->persist($guest); // Prépare l'invité à être enregistré en base de données
            $em->flush(); // Enregistre l'invité en base de données

            // Redirige vers la liste des invités après l'ajout réussi
            return $this->redirectToRoute('admin_guest_index');
        }

        // Affiche le formulaire d'ajout d'invité
        return $this->render('admin/guest/add.html.twig', ['form' => $form]);
    }

    /**
     * Route pour bloquer ou débloquer un invité.
     */
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin/guest/toggle/{id}', name: 'admin_guest_toggle')]
    public function toggle(int $id, UserRepository $userRepository, EntityManagerInterface $em): Response
    {
        // Récupération de l'invité par son ID
        $guest = $userRepository->find($id);

        // Si l'invité n'existe pas, on affiche une page d'erreur 404
        if (!$guest) {
            throw $this->createNotFoundException('Invité introuvable.');
        }

        // Inversion de l'état bloqué/débloqué de l'invité
        $guest->setBlocked(!$guest->isBlocked());
        $em->flush(); // Enregistre les modifications en base de données

        // Redirige vers la liste des invités après la mise à jour de l'état
        return $this->redirectToRoute('admin_guest_index');
    }

    /**
     * Route pour supprimer un invité.
     */
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin/guest/delete/{id}', name: 'admin_guest_delete')]
    public function delete(int $id, UserRepository $userRepository, EntityManagerInterface $em): Response
    {
        // Récupération de l'invité par son ID
        $guest = $userRepository->find($id);

        // Si l'invité n'existe pas, on affiche une page d'erreur 404
        if (!$guest) {
            throw $this->createNotFoundException('Invité introuvable.');
        }

        // Suppression de l'invité (et des données liées - cascade configurée)
        $em->remove($guest); // Prépare l'invité à être supprimé en base de données
        $em->flush(); // Enregistre la suppression en base de données

        // Redirige vers la liste des invités après la suppression réussie
        return $this->redirectToRoute('admin_guest_index');
    }
}
