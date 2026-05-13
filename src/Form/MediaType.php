<?php

namespace App\Form;

use App\Entity\Album;
use App\Entity\Media;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Formulaire d'ajout/édition d'un média.
 *
 * Rôle :
 * - Permet d'uploader une image et de définir son titre.
 * - Si l'utilisateur est admin, permet également d'associer un utilisateur et un album.
 *
 * Champs :
 * - file   : fichier image uploadé.
 * - title  : titre du média.
 * - user   : utilisateur propriétaire (admin uniquement).
 * - album  : album associé (admin uniquement).
 */
class MediaType extends AbstractType
{
    /**
     * Construction du formulaire Media
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Ajout des champs du formulaire pour créer/éditer un média
        $builder
        // champ file image à uploader
            ->add('file', FileType::class, [
                'label' => 'Image',
            ])
            ->add('title', TextType::class, [
                'label' => 'Titre',
            ])
        ;

        // Champs supplémentaires visibles uniquement pour les administrateur
        // propriétaire du média, album associé
        if ($options['is_admin']) {
            $builder
                ->add('user', EntityType::class, [
                    'label' => 'Utilisateur',
                    'required' => false,
                    'class' => User::class,
                    'choice_label' => 'name',
                ])
                ->add('album', EntityType::class, [
                    'label' => 'Album',
                    'required' => false,
                    'class' => Album::class,
                    'choice_label' => 'name',
                ])
            ;
        }
    }

    /**
    * Configuration des options du formulaire
    */
    public function configureOptions(OptionsResolver $resolver): void
    {
        // Lie ce formulaire à l'entité Media pour que les données soient automatiquement mappées
        // Ajoute une option "is_admin" pour conditionner l'affichage de certains champs
        $resolver->setDefaults([
            'data_class' => Media::class,
            'is_admin' => false,// Par défaut, on considère que l'utilisateur n'est pas admin
        ]);
    }
}
