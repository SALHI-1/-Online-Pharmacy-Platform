<?php

namespace App\Controller;

use App\Entity\Medicament;
use App\Repository\MedicamentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Form\MedicamentType;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/medicament')]
#[IsGranted('ROLE_ADMIN')]
class MedicamentController extends AbstractController
{

    #[Route('/new', name: 'app_medicament_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $medicament = new Medicament();
    $form = $this->createForm(MedicamentType::class, $medicament);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
       
       /** @var UploadedFile $imageFile */
       $imageFile = $form->get('image')->getData();

       if ($imageFile) {
           $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
           $newFilename = $originalFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

           try {
               $imageFile->move(
                   $this->getParameter('medicaments_directory'),
                   $newFilename
               );
           } catch (FileException $e) {
           }

           $medicament->setImage($newFilename);
       }
       
       
        try {
            $entityManager->persist($medicament);
            $entityManager->flush();
            
            $this->addFlash('success', 'Le médicament a été ajouté avec succès.');
            
            return $this->json([
                'success' => true,
                'redirect' => $this->generateUrl('app_medicament')
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de l\'ajout du médicament.'
            ], 500);
        }
    }

    return $this->render('home/new.html.twig', [
        'medicament' => $medicament,
        'form' => $form->createView(),
    ]);
    }

    #[Route('/{id}/edit', name: 'app_medicament_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Medicament $medicament, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(MedicamentType::class, $medicament);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Le médicament a été modifié avec succès.');
            return $this->redirectToRoute('app_medicament');
        }
        

        return $this->render('home/edit.html.twig', [
            'medicament' => $medicament,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_medicament_delete', methods: ['POST'])]
public function delete(Request $request, Medicament $medicament, EntityManagerInterface $entityManager): Response
{
    // Validation CSRF
    if ($this->isCsrfTokenValid('delete'.$medicament->getId(), $request->request->get('_token'))) {
        try {
            // Suppression
            $entityManager->remove($medicament);
            $entityManager->flush();
            $this->addFlash('success', 'Le médicament a été supprimé avec succès.');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue lors de la suppression.');
        }
    }
    
    // Retour à la page des médicaments après suppression
    return $this->redirectToRoute('app_medicament');
}

}