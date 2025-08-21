<?php

namespace App\Form;

use App\Entity\Season;
use App\Entity\Serie;
use App\Repository\SerieRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType; // Ajouté si 'number' est un texte, sinon laissez null
use Symfony\Component\Form\Extension\Core\Type\TextareaType; // Ajouté pour 'overview' si vous voulez un textarea
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File; // Ajouté pour les contraintes de fichier

class SeasonType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // Suppression de dateCreated et dateModified qui sont gérés automatiquement
            ->add('serie', EntityType::class, [
                'label' => 'Série associée',
                'placeholder' => '-- Choisir une Série --',
                'class' => Serie::class,
                'choice_label' => function (Serie $serie) {
                    $seasonCount = count($serie->getSeasons());
                    return sprintf('%s (%s saison%s)',
                        $serie->getName(),
                        $seasonCount,
                        $seasonCount > 1 ? 's' : ''
                    );
                },
                'query_builder' => function (SerieRepository $repo) {
                    return $repo->createQueryBuilder('s')
                        ->orderBy('s.name', 'ASC');
                }
            ])
            ->add('number', TextType::class, [ // Type TextType suggéré pour 'number' si ce n'est pas un spinner
                'label' => 'Numéro de la saison',
                'required' => true, // Généralement, le numéro de saison est obligatoire
            ])
            ->add('firstAirDate', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Date de première diffusion',
                'required' => true, // La date de première diffusion est souvent obligatoire
            ])
            ->add('overview', TextareaType::class, [ // Utiliser TextareaType pour un aperçu plus long
                'label' => 'Synopsis de la saison',
                'required' => false,
            ])
            ->add('tmdbId', null, [ // null laissera Symfony deviner le type (parfait pour les entiers)
                'label' => 'ID TMDB de la saison',
                'required' => false,
            ])
            ->add('poster_file', FileType::class, [
                'label' => 'Fichier du poster', // Label plus explicite pour l'upload
                'mapped' => false,
                'required' => false,
                'constraints' => [ // Ajout de contraintes pour les fichiers, comme dans SerieType
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
                'label' => 'Enregistrer la saison', // Texte du bouton plus spécifique
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Season::class,
        ]);
    }
}