<?php

namespace App\Controller;

use App\Entity\Serie;
use App\Form\SerieType;
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

        $criterias = [];
        $genre = $request->query->get('genre');
        $status = $request->query->get('status');

        if ($genre) {
            $criterias['genres'] = $genre;
        }

        if ($status) {
            $criterias['status'] = $status;
        }

        $sortCriteria = [];
        $sortBy = $request->query->get('sort-by');
        if ($sortBy) {
            $parts = explode('_', $sortBy);
            $sortCriteria[$parts[0]] = strtoupper($parts[1]);
        } else {
            // Tri par défaut si rien n'est sélectionné
            $sortCriteria['popularity'] = 'DESC';
        }

        $series = $serieRepository->findBy(
            $criterias,
            $sortCriteria, // Utilisez le critère de tri dynamique
            $nbParPage,
            $offset
        );

        $total = $serieRepository->count($criterias);
        $totalPages = ceil($total / $nbParPage);

        return $this->render('series/list.html.twig', [
            'series' => $series,
            'page' => $page,
            'total_pages' => $totalPages,
            // Passez les critères actuels pour la pagination
            'current_criterias' => array_merge($criterias, ['sort-by' => $sortBy]),
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

