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

class GuestController extends AbstractController
{
    #[Route('/admin/guest', name: 'admin_guest_index')]
    public function index(UserRepository $userRepository): Response
    {
        return $this->render('admin/guest/index.html.twig', [
            'guests' => $userRepository->findGuests(),
        ]);
    }

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
