<?php

namespace App\Controller;

use App\Entity\Serie;
use App\Form\SerieType;
use App\Form\FilterType;
use App\Repository\SerieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\Tools\Pagination\Paginator; // N'oubliez pas d'importer la classe Paginator

final class SerieController extends AbstractController
{
    #[Route('/list/{page}', name: '_list', requirements: ['page' => '\d+'], defaults: ['page' => 1], methods: ['GET'])]
    public function list(SerieRepository $serieRepository, int $page, ParameterBagInterface $parameters, Request $request): Response
    {
        $nbParPage = $parameters->get('serie')['nb_max'];
        $offset = ($page - 1) * $nbParPage;

        $filterForm = $this->createForm(FilterType::class);
        $filterForm->handleRequest($request);

        $genre = $filterForm->get('genre')->getData();
        $status = $filterForm->get('status')->getData();
        $sortBy = $filterForm->get('sortBy')->getData();

        // Créer un QueryBuilder pour construire la requête
        $queryBuilder = $serieRepository->createQueryBuilder('s');

        // Ajouter des conditions si les filtres sont sélectionnés
        if ($genre) {
            $queryBuilder->andWhere('s.genres LIKE :genre')
                ->setParameter('genre', '%' . $genre . '%');
        }

        if ($status) {
            $queryBuilder->andWhere('s.status = :status')
                ->setParameter('status', $status);
        }

        // Ajouter la clause de tri
        if ($sortBy) {
            $parts = explode('_', $sortBy);
            $sortField = $parts[0];
            $sortDirection = strtoupper($parts[1]);
            // Gérer les champs de tri potentiellement différents dans l'entité
            // Par exemple, 'firstAirDate' dans le formulaire correspond à 'first_air_date' dans l'entité
            $dbSortField = match ($sortField) {
                'vote' => 'vote',
                'popularity' => 'popularity',
                'firstAirDate' => 'first_air_date',
                'lastAirDate' => 'last_air_date',
                default => 'popularity', // Fallback
            };
            $queryBuilder->orderBy('s.' . $dbSortField, $sortDirection);
        } else {
            // Tri par défaut
            $queryBuilder->orderBy('s.popularity', 'DESC');
        }

        // Appliquer la pagination (limite et offset)
        $queryBuilder->setFirstResult($offset)
            ->setMaxResults($nbParPage);

        // Utiliser Doctrine Paginator pour récupérer les résultats et le total
        $paginator = new Paginator($queryBuilder->getQuery());

        $series = $paginator->getIterator()->getArrayCopy(); // Récupère les entités pour la page actuelle
        $total = $paginator->count(); // Récupère le nombre total de résultats (optimisé par Paginator)

        $totalPages = ceil($total / $nbParPage);
        $totalPages = $totalPages > 0 ? $totalPages : 1; // Assure au moins 1 page si aucun résultat

        return $this->render('series/list.html.twig', [
            'series' => $series,
            'page' => $page,
            'total_pages' => $totalPages,
            'current_criterias' => array_filter([ // Filtres actuels pour la pagination
                'genre' => $genre,
                'status' => $status,
                'sortBy' => $sortBy
            ]),
            'filter_form' => $filterForm->createView(),
        ]);
    }

    #[Route('/liste-custom', name: '_custom_list')]
    public function listCustom(SerieRepository $serieRepository): Response
    {
        // Exemple : 400 est la popularité minimale, 8 le nombre de séries
        $series = $serieRepository->findSeriesCustom(400, 8);

        // Attention: ces valeurs sont hardcodées, elles devraient être dynamiques si la liste est paginée/filtrée
        return $this->render('series/list.html.twig', [
            'series' => $series,
            'page' => 1,
            'total_pages' => 1, // Si c'est une liste fixe, il n'y a qu'une page
            'current_criterias' => [], // Pas de critères de filtre pour cette liste custom
            'filter_form' => $this->createForm(FilterType::class)->createView(), // Passer un formulaire vide si nécessaire
        ]);
    }

    #[Route('/list/detail/{id}', name: '_detail', requirements: ['id' => '\d+'])]
    public function detail(Serie $serie): Response
    {
        return $this->render('series/detail.html.twig', [
            'serie' => $serie
        ]);
    }

    #[Route('/list/create', name: '_create')]
    public function create(Request $request, EntityManagerInterface $em): Response
    {
        $serie = new Serie();
        $form = $this->createForm(SerieType::class, $serie);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $em->persist($serie);
            $em->flush();

            $this->addFlash('success', "Une série a été enregistrée");

            return $this->redirectToRoute('_detail', ['id' => $serie->getId()]);
        }

        return $this->render('series/edit.html.twig', [
            'serie_form' => $form,
        ]);
    }

    #[Route('/list/update{id}', name: '_update', requirements: ['id' => '\d+'])]
    public function update(Serie $serie, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(SerieType::class, $serie);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', "Une série a été mise à jour");

            return $this->redirectToRoute('_detail', ['id' => $serie->getId()]);
        }

        return $this->render('series/edit.html.twig', [
            'serie_form' => $form,
        ]);
    }

    #[Route('/list/delete{id}', name: '_delete', requirements: ['id' => '\d+'])]
    public function delete(Serie $serie, EntityManagerInterface $em, Request $request): Response
    {
        // Vérifie si le jeton CSRF est valide avant la suppression
        // Note : Le nom du jeton 'delete'.$serie->getId() doit correspondre à celui généré dans le formulaire de suppression
        if ($this->isCsrfTokenValid('delete'.$serie->getId(), $request->request->get('token'))) {
            $em->remove($serie);
            $em->flush();

            $this->addFlash('success', "Une série a été supprimée");
        } else {
            $this->addFlash('danger', "Suppression impossible : Jeton CSRF invalide.");
        }

        return $this->redirectToRoute('_list');
    }
}
