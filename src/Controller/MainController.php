<?php

namespace App\Controller;

use App\Form\FilterType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class MainController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function home(Request $request): Response
    {
        // 1. Crée le formulaire de filtres
        $form = $this->createForm(FilterType::class);

        // 2. Traite la requête (si le formulaire a été soumis)
        $form->handleRequest($request);

        // 3. Passe le formulaire à la vue
        return $this->render('main/home.html.twig', [
            'filter_form' => $form->createView(),
        ]);
    }
}