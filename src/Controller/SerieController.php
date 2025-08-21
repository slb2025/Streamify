<?php

namespace App\Controller;

use App\Entity\Serie;
use App\Form\SerieType;
use App\Form\FilterType;
use App\Repository\SerieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Filesystem\Filesystem; // Ajouté pour la gestion des fichiers
use Symfony\Component\Filesystem\Exception\IOExceptionInterface; // Ajouté pour la gestion des erreurs de fichier
use Symfony\Component\HttpFoundation\File\Exception\FileException; // Ajouté pour la gestion des erreurs d'upload


final class SerieController extends AbstractController
{
    #[Route('/list/{page}', name: '_list', requirements: ['page' => '\d+'], defaults: ['page' => 1], methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function list(SerieRepository $serieRepository, int $page, ParameterBagInterface $parameters, Request $request): Response
    {
        $nbParPage = $parameters->get('serie')['nb_max'];
        $offset = ($page - 1) * $nbParPage;

        $filterForm = $this->createForm(FilterType::class);
        $filterForm->handleRequest($request);

        // Récupère les données du formulaire de filtre
        $criterias = $filterForm->isSubmitted() ? $filterForm->getData() : [];

        // Utilise la nouvelle méthode du repository pour récupérer les séries paginées et filtrées
        $paginator = $serieRepository->findPaginatedAndFiltered($nbParPage, $offset, $criterias);

        $series = iterator_to_array($paginator->getIterator());
        $total = $paginator->count();
        $totalPages = ceil($total / $nbParPage);
        $totalPages = $totalPages > 0 ? $totalPages : 1;

        return $this->render('series/list.html.twig', [
            'series' => $series,
            'page' => $page,
            'total_pages' => $totalPages,
            'current_criterias' => $criterias,
            'filter_form' => $filterForm->createView(),
        ]);
    }


    #[Route('/liste-custom', name: '_custom_list')]
    #[IsGranted('ROLE_USER')]
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
    #[IsGranted('ROLE_USER')]
    public function detail(Serie $serie): Response
    {
        return $this->render('series/detail.html.twig', [
            'serie' => $serie
        ]);
    }

    #[Route('/list/create', name: '_create')]
    #[IsGranted('ROLE_ADMIN')]
    public function create(Request $request, EntityManagerInterface $em, SluggerInterface $slugger, ParameterBagInterface $parameterBag): Response
    {
        $serie = new Serie();
        $form = $this->createForm(SerieType::class, $serie);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            /** @var UploadedFile $file */
            $file = $form->get('poster_file')->getData(); // Récupère le fichier uploadé

            if ($file) { // Si un fichier a été uploadé
                $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                // Utilise le slugger pour générer un nom de fichier sûr
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();

                $posterDirectory = $parameterBag->get('serie')['poster_directory']; // Correction : 'series' -> 'serie'

                try {
                    $file->move(
                        $posterDirectory,
                        $newFilename
                    );
                } catch (FileException $e) {
                    // Gérer l'exception si le fichier ne peut pas être déplacé
                    $this->addFlash('error', 'Impossible d\'uploader le poster : ' . $e->getMessage());
                    return $this->render('series/edit.html.twig', [
                        'serie_form' => $form->createView(), // Correction : Utilise createView()
                    ]);
                }

                $serie->setPoster($newFilename); // Associe le nom du fichier à l'entité
            }

            $em->persist($serie);
            $em->flush();

            $this->addFlash('success', "Une série a été enregistrée");

            return $this->redirectToRoute('_detail', ['id' => $serie->getId()]);
        }

        return $this->render('series/edit.html.twig', [
            'serie_form' => $form->createView(), // Correction : 'series_form' -> 'serie_form' et utilise createView()
        ]);
    }

    #[Route('/list/update{id}', name: '_update', requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ADMIN')]
    public function update(Serie $serie, Request $request, EntityManagerInterface $em, SluggerInterface $slugger, ParameterBagInterface $parameterBag, Filesystem $filesystem): Response // Ajout de Filesystem
    {
        $form = $this->createForm(SerieType::class, $serie);
        $oldPoster = $serie->getPoster(); // Sauvegarde l'ancien nom du poster avant le handleRequest

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            /** @var UploadedFile $file */
            $file = $form->get('poster_file')->getData(); // Récupère le fichier uploadé

            if ($file) { // Si un nouveau fichier a été uploadé
                $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();

                $posterDirectory = $parameterBag->get('serie')['poster_directory'];

                try {
                    $file->move(
                        $posterDirectory,
                        $newFilename
                    );
                } catch (FileException $e) {
                    // Gérer l'exception
                    $this->addFlash('error', 'Impossible d\'uploader le nouveau poster : ' . $e->getMessage());
                    return $this->render('series/edit.html.twig', [
                        'serie_form' => $form->createView(), // Correction : Utilise createView()
                    ]);
                }

                // Supprime l'ancien poster si un nouveau a été uploadé et qu'il existait
                if ($oldPoster && $filesystem->exists($posterDirectory . '/' . $oldPoster)) {
                    try {
                        $filesystem->remove($posterDirectory . '/' . $oldPoster);
                    } catch (IOExceptionInterface $e) {
                        $this->addFlash('error', 'Impossible de supprimer l\'ancien poster : ' . $e->getMessage());
                        // Ne pas bloquer la mise à jour pour autant
                    }
                }

                $serie->setPoster($newFilename); // <-- Correction : Associe le nouveau nom de fichier à l'entité
            } elseif ($form->get('poster_file')->getData() === null && !$form->get('poster_file')->isRequired() && $oldPoster) {
                // Si le champ de fichier est vidé (pas de nouveau fichier) et que le champ n'est pas requis
                // et qu'il y avait un ancien poster, le supprimer et mettre le champ de l'entité à null
                $posterDirectory = $parameterBag->get('serie')['poster_directory'];
                $filePath = $posterDirectory . '/' . $oldPoster;
                if ($filesystem->exists($filePath)) {
                    try {
                        $filesystem->remove($filePath);
                        $serie->setPoster(null); // Mettre à null en BDD
                    } catch (IOExceptionInterface $e) {
                        $this->addFlash('error', 'Impossible de supprimer l\'ancien poster : ' . $e->getMessage());
                    }
                }
            }


            $em->flush();

            $this->addFlash('success', "Une série a été mise à jour");

            return $this->redirectToRoute('_detail', ['id' => $serie->getId()]);
        }

        return $this->render('series/edit.html.twig', [
            'serie_form' => $form->createView(),
        ]);
    }

    #[Route('/list/delete{id}', name: '_delete', requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Serie $serie, EntityManagerInterface $em, Request $request, ParameterBagInterface $parameterBag, Filesystem $filesystem): Response // Ajout de Filesystem
    {
        // Optionnel : Supprimer le fichier poster associé avant de supprimer l'entité
        $posterFilename = $serie->getPoster();
        if ($posterFilename) {
            $posterDirectory = $parameterBag->get('serie')['poster_directory'];
            $filePath = $posterDirectory . '/' . $posterFilename;
            if ($filesystem->exists($filePath)) {
                try {
                    $filesystem->remove($filePath);
                    $this->addFlash('info', "Le poster '$posterFilename' a été supprimé du disque.");
                } catch (IOExceptionInterface $e) {
                    $this->addFlash('error', "Impossible de supprimer le poster '$posterFilename' : " . $e->getMessage());
                }
            }
        }

        // Vérifie si le jeton CSRF est valide avant la suppression
        // Note : Le nom du jeton 'delete'.$serie->getId() doit correspondre à celui généré dans le formulaire de suppression
        // Utilise $request->request->get('token') pour les données POST
        if ($this->isCsrfTokenValid('delete'.$serie->getId(), $request->get('token'))) { // Correction ici
            $em->remove($serie);
            $em->flush();

            $this->addFlash('success', "Une série a été supprimée");
        } else {
            $this->addFlash('danger', "Suppression impossible : Jeton CSRF invalide.");
        }

        return $this->redirectToRoute('_list');
    }
}
