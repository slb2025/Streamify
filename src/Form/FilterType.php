<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('genre', ChoiceType::class, [
                'label' => 'Genre :',
                'choices' => [
                    'Tous' => '',
                    'Drama' => 'Drama',
                    'Comedy' => 'Comedy',
                    'Science-Fiction' => 'Science-Fiction',
                ],
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Statut :',
                'choices' => [
                    'Tous' => '',
                    'En cours' => 'Returning',
                    'Terminé' => 'Ended',
                    'Abandonné' => 'Canceled',
                ],
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