<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('search', TextType::class, [ // Ajoutez ce champ
                'label' => 'Rechercher par nom :',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Entrez le nom d\'une série...',
                ],
            ])
            ->add('genre', ChoiceType::class, [
                'label' => 'Genre :',
                'choices' => [
                    'Tous' => '',
                    'Action' => 'Action',
                    'Aventure' => 'Adventure',
                    'Comédie' => 'Comedy',
                    'Crime' => 'Crime',
                    'Drame' => 'Drama',
                    'Famille' => 'Family',
                    'Fantaisie' => 'Fantasy',
                    'Horreur' => 'Horror',
                    'Mystère' => 'Mystery',
                    'Politique' => 'Politics',
                    'Romantique' => 'Romance',
                    'Science-Fiction' => 'Sci-Fi',
                    'Feuilleton' => 'Soap',
                    'Guerre' => 'War',
                    'Western' => 'Western',
                ],
                'required' => false,
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Statut :',
                'choices' => [
                    'Tous' => '',
                    'En cours' => 'Returning',
                    'Terminé' => 'Ended',
                    'Abandonné' => 'Canceled',
                ],
                'required' => false,
            ])
            ->add('sortBy', ChoiceType::class, [
                'label' => 'Trier par :',
                'choices' => [
                    '---' => '',
                    'Vote (Décroissant)' => 'vote_desc',
                    'Vote (Croissant)' => 'vote_asc',
                    'Popularité (Décroissant)' => 'popularity_desc',
                    'Popularité (Croissant)' => 'popularity_asc',
                    'Date de sortie (Décroissant)' => 'firstAirDate_desc',
                    'Date de sortie (Croissant)' => 'firstAirDate_asc',
                    'Dernière saison (Décroissant)' => 'lastAirDate_desc',
                    'Dernière saison (Croissant)' => 'lastAirDate_asc',
                ],
                'required' => false,
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Filtrer',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'method' => 'GET',
            'csrf_protection' => false,
        ]);
    }
}