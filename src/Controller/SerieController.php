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
            $queryBuilder->orderBy('s.' . $sortField, $sortDirection);
        } else {
            $queryBuilder->orderBy('s.popularity', 'DESC');
        }

        // Appliquer la pagination (limite et offset)
        $queryBuilder->setFirstResult($offset)
            ->setMaxResults($nbParPage);

        // Exécuter la requête
        $series = $queryBuilder->getQuery()->getResult();

        // Créer un autre QueryBuilder pour le comptage total
        $countQueryBuilder = $serieRepository->createQueryBuilder('s');
        if ($genre) {
            $countQueryBuilder->andWhere('s.genres LIKE :genre')
                ->setParameter('genre', '%' . $genre . '%');
        }
        if ($status) {
            $countQueryBuilder->andWhere('s.status = :status')
                ->setParameter('status', $status);
        }

        $total = $countQueryBuilder->select('count(s.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $totalPages = ceil($total / $nbParPage);

        return $this->render('series/list.html.twig', [
            'series' => $series,
            'page' => $page,
            'total_pages' => $totalPages,
            // Passer les critères de filtre actuels pour la pagination
            'current_criterias' => array_filter([
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
        $series = $serieRepository->findSeriesCustom(400, 8);

        return $this->render('series/list.html.twig', [
            'series' => $series,
            'page' => 1,
            'total_pages' => 10,
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

        if ($form->isSubmitted()) {

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

        if ($form->isSubmitted()) {
            $em->flush();

            $this->addFlash('success', "Une série a été mise à jour");

            return $this->redirectToRoute('_detail', ['id' => $serie->getId()]);
        }

        return $this->render('series/edit.html.twig', [
            'serie_form' => $form,
        ]);
    }
}