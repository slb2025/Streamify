<?php

namespace App\Controller;

use App\Form\FilterType;
use App\Repository\SerieRepository; // Importation de SerieRepository
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class MainController extends AbstractController
{
    #[Route('/', name: 'home')] // Nom de route 'home' comme dans base.html.twig
    public function home(Request $request, SerieRepository $serieRepository): Response // Injection du repository
    {
        // 1. Crée le formulaire de filtres
        $form = $this->createForm(FilterType::class);

        // 2. Traite la requête (si le formulaire a été soumis)
        $form->handleRequest($request);

        // 3. Récupère 3 séries recommandées
        // Nous allons définir cette méthode dans SerieRepository
        $recommendedSeries = $serieRepository->findRecommended(3);

        // 4. Passe le formulaire et les séries à la vue
        return $this->render('main/home.html.twig', [
            'filter_form' => $form->createView(),
            'recommended_series' => $recommendedSeries, // Nouvelle variable passée à Twig
        ]);
    }
}
