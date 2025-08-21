<?php

namespace App\Form;

use App\Entity\Serie;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class SerieType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom de la série',
                'required' => false,
            ])
            ->add('overview', TextareaType::class, [
                'label' => 'Synopsis',
                'required' => false,
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'En cours' => 'returning',
                    'Terminé' => 'ended',
                    'Abandonné' => 'Canceled',
                ],
                'required' => false,
                'placeholder' => '-- Choisissez un statut --',
            ])
            ->add('vote', null, [
                'label' => 'Nombre de votes',
                'required' => false,
            ])
            ->add('popularity', null, [
                'label' => 'Popularité',
                'required' => false,
            ])
            ->add('genres', null, [
                'label' => 'Genre',
                'required' => false,
            ])
            ->add('firstAirDate', DateType::class, [
                'label' => 'Date de première diffusion',
                'widget' => 'single_text',
                'required' => true,
            ])
            ->add('lastAirDate', DateType::class, [
                'label' => 'Date de dernière diffusion',
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('backdrop', null, [
                'label' => 'Nom du fichier de fond',
                'required' => false,
            ])
            ->add('poster_file', FileType::class, [
                'label' => 'Nom du fichier du poster',
                'required' => false,
                'mapped' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '1024k',
                        'maxSizeMessage' => 'Votre fichier est trop lourd !',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/gif',
                            'image/webp',
                        ],
                        'mimeTypesMessage' => 'Les formats acceptés sont jpeg, png, gif ou webp',
                    ])
                ]

            ])
            ->add('save', SubmitType::class, [
                'label' => 'Enregistrer la série',
        ]);
//            ->add('tmdbId')
//            ->add('dateCreated')
//            ->add('dateModified')
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Serie::class,
        ]);
    }
}
