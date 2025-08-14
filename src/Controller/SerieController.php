<?php

namespace App\Controller;

use App\Entity\Serie;
use App\Repository\SerieRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SerieController extends AbstractController
{
    #[Route('/list/{page}', name: '_list', requirements: ['page' => '\d+'], defaults: ['page' => 1], methods: ['GET'])]
    public function list(SerieRepository $serieRepository, int $page, ParameterBagInterface $parameters): Response
    {
        //$series=$serieRepository->findAll();

        $nbParPage =$parameters->get('serie')['nb_max'];
        $offset = ($page -1) * $nbParPage;
        $criterias = [
            'status'=>'Returning',
            'genres'=> 'Drama',
        ];

        $series = $serieRepository->findBy(
            $criterias,
            ['popularity'=>'DESC'],
            $nbParPage,
            $offset
        );

        $total = $serieRepository->count($criterias);
        $totalPages = ceil($total/$nbParPage);

        return $this->render('series/list.html.twig', [
            'series' => $series,
            'page' => $page,
            'total_pages' => $totalPages,
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

    #[Route('/list/detail/{id}', name: '_detail')]
    public function detail(int $id, SerieRepository $serieRepository): Response
    {
        $serie = $serieRepository->find($id);

        if (!$serie) {
            throw $this->createNotFoundException('Pas de série pour cet id');
        }
        return $this->render('series/detail.html.twig', [
            'serie' => $serie
        ]);
    }
}
