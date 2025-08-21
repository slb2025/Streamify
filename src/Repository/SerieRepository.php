<?php

namespace App\Repository;

use App\Entity\Serie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator; // Assurez-vous que cette ligne est bien présente !
use Doctrine\Persistence\ManagerRegistry;

class SerieRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Serie::class);
    }

    /**
     * Récupère une liste de séries recommandées, triées par popularité.
     *
     * @param int $limit Le nombre maximum de séries à retourner.
     * @return Serie[] Retourne un tableau d'objets Serie.
     */
    public function findRecommended(int $limit = 3): array
    {
        return $this->createQueryBuilder('s')
            ->orderBy('s.popularity', 'DESC') // Trie par popularité décroissante
            ->setMaxResults($limit)           // Limite le nombre de résultats (par défaut 3)
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère une liste de séries basées sur des critères personnalisés (popularité, vote).
     * Les paramètres sont hardcodés ici.
     *
     * @return Serie[] Retourne un tableau d'objets Serie.
     */
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

    /**
     * Récupère une liste de séries en utilisant DQL (Doctrine Query Language).
     *
     * @param float $popularity Le seuil de popularité.
     * @param float $vote Le seuil de vote.
     * @return Serie[] Retourne un tableau d'objets Serie.
     */
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

    /**
     * Récupère une liste de séries en utilisant du SQL brut.
     *
     * @param float $popularity Le seuil de popularité.
     * @param float $vote Le seuil de vote.
     * @return array Retourne un tableau associatif de résultats.
     */
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

    /**
     * Récupère les séries paginées avec leurs saisons associées.
     *
     * @param int $nbParPage Le nombre de résultats par page.
     * @param int $offset L'offset pour la pagination.
     * @return Paginator Retourne un objet Paginator.
     */
    public function getSeriesWithSeasons(int $nbParPage, int $offset): Paginator {

        $q = $this->createQueryBuilder('s')
            ->orderBy('s.popularity', 'DESC')
            ->leftJoin('s.seasons', 'seasons')
            ->addSelect('seasons')
            ->setFirstResult($offset)
            ->setMaxResults($nbParPage)
            ->getQuery();

        return new Paginator($q);
    }

    /**
     * Récupère les séries paginées et filtrées, avec leurs saisons.
     * C'est la méthode utilisée par l'action 'list' du contrôleur pour la liste principale.
     *
     * @param int $nbParPage Le nombre de résultats par page.
     * @param int $offset L'offset pour la pagination.
     * @param array $criterias Les critères de filtre et de tri (genre, status, sortBy).
     * @return Paginator Retourne un objet Paginator.
     */
    public function findPaginatedAndFiltered(int $nbParPage, int $offset, array $criterias): Paginator
    {
        $queryBuilder = $this->createQueryBuilder('s');

        // Jointure pour charger les saisons en même temps que les séries (évite le problème N+1)
        $queryBuilder->leftJoin('s.seasons', 'seasons')
            ->addSelect('seasons');

        if (!empty($criterias['search'])) {
            $queryBuilder->andWhere('s.name LIKE :search')
                ->setParameter('search', '%' . $criterias['search'] . '%');
        }

        // Ajout des conditions de filtrage si elles sont présentes dans les critères
        if (!empty($criterias['genre'])) {
            $queryBuilder->andWhere('s.genres LIKE :genre')
                ->setParameter('genre', '%' . $criterias['genre'] . '%');
        }

        if (!empty($criterias['status'])) {
            $queryBuilder->andWhere('s.status = :status')
                ->setParameter('status', $criterias['status']);
        }

        // Ajout de la clause de tri
        if (!empty($criterias['sortBy'])) {
            $parts = explode('_', $criterias['sortBy']); // Sépare le champ et la direction (ex: 'popularity_desc')
            $sortField = $parts[0];
            $sortDirection = strtoupper($parts[1]); // Convertit la direction en majuscules (ASC/DESC)

            // Mappe le nom du champ du formulaire au nom de l'attribut de l'entité
            $dbSortField = match ($sortField) {
                'vote' => 'vote',
                'popularity' => 'popularity',
                'firstAirDate' => 'first_air_date', // Attention à la correspondance camelCase/snake_case
                'lastAirDate' => 'last_air_date',
                default => 'popularity', // Tri par défaut si le champ n'est pas reconnu
            };

            $queryBuilder->orderBy('s.' . $dbSortField, $sortDirection);
        } else {
            // Tri par défaut si aucun tri n'est spécifié par le formulaire
            $queryBuilder->orderBy('s.popularity', 'DESC');
        }

        // Applique la pagination (offset et limite)
        $queryBuilder->setFirstResult($offset)
            ->setMaxResults($nbParPage);

        // Retourne un objet Paginator pour une gestion facile de la pagination et du comptage total.
        return new Paginator($queryBuilder);
    }


}