<?php

namespace App\Repository;

use App\Entity\Serie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SerieRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Serie::class);
    }

    // Ajout de la nouvelle méthode pour trouver les séries recommandées
    public function findRecommended(int $limit = 3): array
    {
        return $this->createQueryBuilder('s')
            ->orderBy('s.popularity', 'DESC') // Trie par popularité décroissante
            ->setMaxResults($limit)           // Limite le nombre de résultats (par défaut 3)
            ->getQuery()
            ->getResult();
    }

    // Méthode : query builder (existante)
    public function findSeriesCustom(): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.popularity > :popularity')
            ->andWhere('s.vote > :vote')
            ->orderBy('s.popularity', 'DESC')
            ->addOrderBy('s.firstAirDate', 'DESC')
            ->setParameter('popularity', 960)
            ->setParameter('vote', 8)
            ->setFirstResult(0)
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();
    }

    // Autre méthode : dql (existante)
    public function findSeriesWithDQL(float $popularity, float $vote): array
    {
        $dql = <<<SQL
                    SELECT s FROM App\Entity\Serie s 
                    WHERE (s.popularity > :popularity or s.firstAirDate > :date) AND s.vote > :vote 
                    ORDER BY s.popularity DESC, s.firstAirDate DESC
                    SQL;

        return $this->getEntityManager()->createQuery($dql)
            ->setFirstResult(0)
            ->setMaxResults(10)
            ->setParameter('popularity', $popularity)
            ->setParameter('vote', $vote)
            ->setParameter('date', new \DateTime('- 5 years'))
            ->getResult();
    }

    // Autre méthode : raw SQL (existante)
    public function getSeriesWithSQL(float $popularity, float $vote): array
    {
        $sql = <<<SQL
            SELECT * FROM serie s 
            WHERE (s.popularity > :popularity or s.firstAirDate > :date)
            AND s.vote > :vote
            ORDER BY s.popularity DESC, s.firstAirDate DESC
            LIMIT 10 OFFSET 0
        SQL;

        $conn = $this->getEntityManager()->getConnection();
        return $conn->prepare($sql)
            ->executeQuery([
                'popularity' => $popularity,
                'date' => (new \DateTime('- 5 years'))->format('Y-m-d'),
                'vote' => $vote,
            ])
            ->fetchAllAssociative();
    }
}
