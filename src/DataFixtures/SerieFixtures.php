<?php

namespace App\DataFixtures;

use App\Entity\Serie;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class SerieFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {

        $faker = Factory::create('fr_FR');

        for($i = 0; $i < 1000; $i++) {
            // On instancie une nouvelle série
            $serie = new Serie();
            // Ajout de la dépendance faker pour générer de la donnée
            $serie->setName($faker->realText(20))
                ->setOverview($faker->paragraph(2))
                ->setStatus($faker->randomElement(['Returning', 'Ended', 'Canceled']))
                ->setVote($faker->randomFloat(2, 0, 10))
                ->setPopularity($faker->randomFloat(2, 0, 1000))
                ->setBackdrop($faker->randomElement(['1', '2', '3', '4', '5', '6', '7', '8', '9']))
                ->setGenres($faker->randomElement(['Drama', 'Romance', 'Thriller', 'Comedy', 'Horror', 'Western']))
                ->setFirstAirDate($faker->dateTimeBetween('-1 year', '-1 month'))
                ->setdateCreated($faker->dateTime(new \DateTime()));

            if ($serie->getStatus() !== 'Returning') {
                $serie->setLastAirDate($faker->dateTimeBetween($serie->getFirstAirDate(), '-1 day'));
            }
            // Manager accumule les objets jusqu'à la fin de la boucle
            // On peut utiliser un modulo pour éviter les pbs de mémoire
            $manager->persist($serie);
        }
        // Puis il les charge dans la DB
        $manager->flush();
    }
}
