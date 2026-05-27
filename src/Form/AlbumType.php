<?php

namespace App\Form;

use App\Entity\Album;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Formulaire de création/édition d'un album.
 *
 * Rôle :
 * - Permet de saisir le nom d'un album.
 * - Utilisé dans l'administration pour gérer les albums.
 *
 * Champs :
 * - name : nom de l'album (champ texte).
 */
class AlbumType extends AbstractType
{
    /**
     * Construction du formulaire Album.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Ajout d'un champ "name" de type texte pour le nom de l'album
        $builder->add('name', TextType::class, [
            'label' => 'Nom',
        ]);
    }

    /**
     * Configuration des options du formulaire.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        // Lie ce formulaire à l'entité Album pour que les données soient automatiquement mappées
        $resolver->setDefaults([
            'data_class' => Album::class,
        ]);
    }
}
