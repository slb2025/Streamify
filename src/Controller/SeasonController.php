<?php

namespace App\Controller;

use App\Entity\Season;
use App\Form\SeasonType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

final class SeasonController extends AbstractController
{
    #[Route('/season/create', name: 'season_create')]
    #[IsGranted("ROLE_MODERATOR")]
    #[Route('/season/update/{id}', name: 'season_update', requirements: ['id' => '\d+'])]
    public function edit(
        ?Season $season, // Utiliser le paramètre d'autowiring pour la mise à jour
        Request $request,
        EntityManagerInterface $em,
        SluggerInterface $slugger,
        ParameterBagInterface $parameterBag
    ): Response
    {
        if (!$season) {
            $season = new Season();
        }
        $form = $this->createForm(SeasonType::class, $season);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile|null $file */
            $file = $form->get('poster_file')->getData();

            if ($file) {
                // ... (logique d'upload de fichier comme dans SerieController)
                $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();
                $posterDirectory = $parameterBag->get('season')['poster_directory'];

                try {
                    $file->move($posterDirectory, $newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Impossible d\'uploader le poster : ' . $e->getMessage());
                    return $this->render('season/edit.html.twig', [
                        'season_form' => $form->createView(),
                        'mode' => 'create',
                    ]);
                }
                $season->setPoster($newFilename);
            }

            $em->persist($season);
            $em->flush();

            $this->addFlash('success', 'La saison a été enregistrée');
            return $this->redirectToRoute('_detail', ['id' => $season->getSerie()->getId()]);
        }

        return $this->render('season/edit.html.twig', [
            'season_form' => $form->createView(),
            'mode' => $season->getId() ? 'update' : 'create',
        ]);
    }
}