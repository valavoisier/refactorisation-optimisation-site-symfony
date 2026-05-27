<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\PasswordStrength;

/**
 * Formulaire d'ajout d'un invité.
 *
 * Rôle :
 * - Permet de créer un utilisateur invité (ROLE_USER) depuis l'administration.
 * - Gère la saisie du nom, email, mot de passe et description.
 *
 * Champs :
 * - name          : nom de l'invité.
 * - email         : adresse e-mail.
 * - plainPassword : mot de passe non mappé, validé puis hashé dans le contrôleur.
 * - description   : description optionnelle.
 */
class GuestType extends AbstractType
{
    /**
     * Construction du formulaire Guest.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Ajout des champs du formulaire pour créer un invité
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom',
            ])
            ->add('email', EmailType::class, [
                'label' => 'Adresse e-mail',
            ])
            ->add('plainPassword', PasswordType::class, [
                'label' => 'Mot de passe',
                'mapped' => false,
                'constraints' => [
                    new NotBlank(message: 'Le mot de passe ne peut pas être vide.'),
                    new Length(
                        min: 12,
                        minMessage: 'Le mot de passe doit contenir au moins {{ limit }} caractères.',
                    ),
                    new PasswordStrength(
                        minScore: PasswordStrength::STRENGTH_WEAK,
                        message: 'Le mot de passe est trop faible. Utilisez un mélange de lettres, chiffres et caractères spéciaux.',
                    ),
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
            ])
        ;
    }

    /**
     * Configuration des options du formulaire.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        // Lie ce formulaire à l'entité User pour que les données soient automatiquement mappées
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
