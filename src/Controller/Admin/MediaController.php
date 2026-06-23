<?php

namespace App\Controller\Admin;

use App\Entity\Media;
use App\Entity\User;
use App\Form\MediaType;
use App\Repository\MediaRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Contrôleur d'administration dédié à la gestion des médias.
 *
 * Rôle :
 * - Permet à l'administrateur de consulter, ajouter et supprimer les médias.
 * - Les administrateurs voient tous les médias.
 * - Les invités connectés ne voient que leurs propres médias.
 *
 * Actions disponibles :
 * - index()  : liste paginée des médias.
 * - add()    : ajoute un nouveau média (upload).
 * - delete() : supprime un média existant.
 */
class MediaController extends AbstractController
{
    /**
     * Route pour afficher la liste paginéedes médias.
     */
    #[Route('/admin/media', name: 'admin_media_index')]
    public function index(Request $request, MediaRepository $mediaRepository): Response
    {
        // Récupération du numéro de page depuis la requête (par défaut 1)
        $page = $request->query->getInt('page', 1);

        // Les admins voient tous les médias, les invités voient seulement les leurs
        // si admin, $user = null (aucun filtrage), sinon on récupère l'utilisateur connecté getUser()
        $user = $this->isGranted('ROLE_ADMIN') ? null : $this->getUser();
        // Vérification que l'utilisateur est bien connecté et de type User
        assert(null === $user || $user instanceof User);

        // Récupération des médias avec utilisateur et album chargés dans la même requête
        $medias = $mediaRepository->findPaginatedWithRelations($user, $page);
        // Comptage total des médias pour la pagination (filtré par utilisateur si invité)
        $total = $mediaRepository->count(null !== $user ? ['user' => $user] : []);

        // Affichage de la page d'administration des médias avec les données récupérées
        return $this->render('admin/media/index.html.twig', [
            'medias' => $medias,
            'total' => $total,
            'page' => $page,
        ]);
    }

    /**
     * Route pour ajouter un nouveau média.
     */
    #[Route('/admin/media/add', name: 'admin_media_add')]
    public function add(Request $request, EntityManagerInterface $em): Response
    {
        // Création d'une nouvelle instance de Media (objet vide)
        $media = new Media();
        // Création du formulaire lié à l'entité Media (MediaType)
        $form = $this->createForm(MediaType::class, $media, ['is_admin' => $this->isGranted('ROLE_ADMIN')]);
        // Hydrate l'objet Media avec les données envoyées (si POST)
        $form->handleRequest($request);

        // Vérifie si le formulaire est soumis et valide
        if ($form->isSubmitted() && $form->isValid()) {
            // Si l'utilisateur n'est pas admin, on associe le média à l'utilisateur connecté
            // Un invité ne peut ajouter que ses propres médias
            if (!$this->isGranted('ROLE_ADMIN')) {
                $user = $this->getUser();
                // Vérification que l'utilisateur est bien connecté et de type User
                assert($user instanceof User);
                $media->setUser($user);
            }
            // Génère un nom de fichier unique pour éviter les collisions et stocke le média dans le dossier "uploads"
            $media->setPath('uploads/'.md5(uniqid()).'.'.$media->getFile()->guessExtension());
            // Déplace le fichier uploadé vers le dossier "uploads" avec le nom généré
            $media->getFile()->move('uploads/', $media->getPath());
            $em->persist($media); // Prépare le média à être enregistré en base de données
            $em->flush(); // Enregistre le média en base de données

            // Redirige vers la liste des médias après l'ajout réussi
            return $this->redirectToRoute('admin_media_index');
        }

        // Affiche le formulaire d'ajout de média
        return $this->render('admin/media/add.html.twig', ['form' => $form]);
    }

    /**
     * Route pour supprimer un média existant.
     */
    #[Route('/admin/media/delete/{id}', name: 'admin_media_delete')]
    public function delete(
        int $id,
        MediaRepository $mediaRepository,
        EntityManagerInterface $em,
    ): Response {
        // Récupération du média à supprimer par son ID
        $media = $mediaRepository->find($id);

        // Si le média n'existe pas, on affiche une page d'erreur 404
        if (!$media) {
            throw $this->createNotFoundException('Média introuvable.');
        }
        // Seuls l'admin peut supprimer n'importe quel média,
        // les autres utilisateurs ne peuvent supprimer que leurs propres médias
        // Vérification des droits d'accès : si l'utilisateur n'est pas admin et que le média ne lui appartient pas, on refuse l'accès
        if (!$this->isGranted('ROLE_ADMIN') && $media->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas supprimer ce média.');
        }

        $em->remove($media); // Prépare le média à être supprimé en base de données
        $em->flush(); // Enregistre la suppression en base de données

        // Redirige vers la liste des médias après la suppression réussie
        return $this->redirectToRoute('admin_media_index');
    }
}
