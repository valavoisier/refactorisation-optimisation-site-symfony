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
     * Route pour afficher la liste des invités
     */
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin/guest', name: 'admin_guest_index')]
    public function index(UserRepository $userRepository): Response
    {
        return $this->render('admin/guest/index.html.twig', [
            'guests' => $userRepository->findGuests(),
        ]);
    }
    /**
     * Route pour ajouter un nouvel invité
     */
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin/guest/add', name: 'admin_guest_add')]
    public function add(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher,
    ): Response {
        $guest = new User();
        $form = $this->createForm(GuestType::class, $guest);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $guest->setPassword(
                $hasher->hashPassword($guest, $form->get('plainPassword')->getData())
            );
            $guest->setRoles(['ROLE_USER']);
            $em->persist($guest);
            $em->flush();

            return $this->redirectToRoute('admin_guest_index');
        }

        return $this->render('admin/guest/add.html.twig', ['form' => $form]);
    }

    /**
     * Route pour bloquer ou débloquer un invité
     */
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin/guest/toggle/{id}', name: 'admin_guest_toggle')]
    public function toggle(int $id, UserRepository $userRepository, EntityManagerInterface $em): Response
    {
        $guest = $userRepository->find($id);

        if (!$guest) {
            throw $this->createNotFoundException('Invité introuvable.');
        }

        $guest->setBlocked(!$guest->isBlocked());
        $em->flush();

        return $this->redirectToRoute('admin_guest_index');
    }

    /**
     * Route pour supprimer un invité
     */
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin/guest/delete/{id}', name: 'admin_guest_delete')]
    public function delete(int $id, UserRepository $userRepository, EntityManagerInterface $em): Response
    {
        $guest = $userRepository->find($id);

        if (!$guest) {
            throw $this->createNotFoundException('Invité introuvable.');
        }

        $em->remove($guest);
        $em->flush();

        return $this->redirectToRoute('admin_guest_index');
    }
}
