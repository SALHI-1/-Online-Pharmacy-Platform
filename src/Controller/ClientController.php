<?php

namespace App\Controller;

use App\Entity\Client;
use App\Entity\CommandeSpec;
use App\Entity\Commande;
use App\Form\ClientRegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use App\Entity\Medicament;
use Knp\Component\Pager\PaginatorInterface;
use App\Service\PanierService;
use App\Service\CommandeService;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Bundle\SecurityBundle\Security;
use App\Form\ProfileType;

use Symfony\Component\Security\Core\User\UserInterface;

class ClientController extends AbstractController
{
    #[Route('/client/register', name: 'app_client_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager): Response
    {
        $client = new Client();
        $form = $this->createForm(ClientRegistrationFormType::class, $client);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $client->setPassword(
                $userPasswordHasher->hashPassword(
                    $client,
                    $form->get('plainPassword')->getData()
                )
            );

            $entityManager->persist($client);
            $entityManager->flush();

            return $this->redirectToRoute('app_client_login');
        }

        return $this->render('client/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    #[Route('/client/login', name: 'app_client_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_client_dashboard');
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('client/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/client/dashboard', name: 'app_client_dashboard')]
    public function dashboard(): Response
    {
        return $this->render('client/dashboard.html.twig');
    }

    #[Route('/client/logout', name: 'app_client_logout')]
    public function logout(): void
    {
        // sera intercepté par la configuration de sécurité
    }
    #[Route('/client/medicament', name: 'app_client_medicament')]
    public function medicament(Request $request, PaginatorInterface $paginator, EntityManagerInterface $em)
    {
        // Récupérer les paramètres de recherche
        $search = $request->query->get('search');
        $type = $request->query->get('type');
        
        // Créer une requête de base pour récupérer les médicaments
        $queryBuilder = $em->getRepository(Medicament::class)->createQueryBuilder('m');
        
        // Appliquer les filtres de recherche
        if ($search) {
            $queryBuilder->andWhere('m.nom LIKE :search')
                         ->setParameter('search', '%'.$search.'%');
        }
        
        if ($type) {
            $queryBuilder->andWhere('m.type = :type')
                         ->setParameter('type', $type);
        }
        
        $queryBuilder->orderBy('m.nom', 'ASC'); // Ordonner par nom
        
        // Utiliser la pagination
        $medicaments = $paginator->paginate(
            $queryBuilder, 
            $request->query->getInt('page', 1), // page actuelle
            6 // Nombre d'éléments par page
        );

        // Rendu de la vue
        return $this->render('client/medicament.html.twig', [
            'medicaments' => $medicaments,
        ]);
    }
    #[Route('client/panier', name: 'app_panier')]
    public function afficherPanier(PanierService $panierService): Response
    {
        $panier = $panierService->getPanier();

        return $this->render('client/panier.html.twig', [
            'panier' => $panier['items'],
            'total' => $panier['total'],
        ]);
    }

    #[Route('client/panier/ajouter/{id}', name: 'app_panier_ajouter')]
    public function ajouterAuPanier(int $id, PanierService $panierService): Response
    {
        $panierService->ajouter($id);
        $this->addFlash('success', 'Votre commande a été ajouter au panier avec succès!');

        return $this->redirectToRoute('app_client_medicament');
    }

    #[Route('client/panier/supprimer/{id}', name: 'app_panier_supprimer')]
    public function supprimerDuPanier(int $id, PanierService $panierService): Response
    {
        $panierService->supprimer($id);

        return $this->redirectToRoute('app_panier');
    }

    #[Route('client/panier/vider', name: 'app_panier_vider')]
    public function viderPanier(PanierService $panierService): Response
    {
        $panierService->vider();

        return $this->redirectToRoute('app_panier');
    }
    #[Route('client/commande/confirmer', name: 'app_commande_confirmer')]
public function confirmerAchat(PanierService $panierService, CommandeService $commandeService): Response
{
    try {
        $pdfPath = $panierService->confirmerAchat($commandeService);

        return $this->file($pdfPath, 'recu.pdf', ResponseHeaderBag::DISPOSITION_ATTACHMENT);
    } catch (\Exception $e) {
        $this->addFlash('error', $e->getMessage());
        return $this->redirectToRoute('app_panier');
    }
}

#[Route('/client/commande/speciale', name: 'app_client_commande_speciale', methods: ['POST'])]
public function commanderSpeciale(Request $request, EntityManagerInterface $em, Security $security): Response
{
    // Vérification du token CSRF
    if (!$this->isCsrfTokenValid('commande_speciale', $request->request->get('_token'))) {
        $this->addFlash('error', 'Token CSRF invalide.');
        return $this->redirectToRoute('app_client_medicament');
    }

    // Récupération des données du formulaire
    $medicamentId = $request->request->get('medicament_id');
    $attestationFile = $request->files->get('attestationMedicale');
    $quantite = (int) $request->request->get('quantite');

    // Debug pour voir les valeurs
    // dump($medicamentId);
    // dump($attestationFile);
    // dump($quantite);

    // Récupérer le médicament et le client actuel
    $medicament = $em->getRepository(Medicament::class)->find($medicamentId);
    $client = $security->getUser();

    if (!$medicament) {
        $this->addFlash('error', 'Médicament introuvable.');
        return $this->redirectToRoute('app_client_medicament');
    }

    if (!$client) {
        $this->addFlash('error', 'Vous devez être connecté pour effectuer cette action.');
        return $this->redirectToRoute('app_login');
    }

    // Traitement du fichier
    $newFilename = 'attestation-'.uniqid().'.'.$attestationFile->guessExtension();
    
    try {
        $attestationFile->move(
            $this->getParameter('attestations_directory'),
            $newFilename
        );

        // Création de la commande
        $commande = new Commande();
        $commande->setClient($client)
                ->setMedicament($medicament)
                ->setDate(new \DateTime())
                ->setStatut('En cours')
                ->setQuantite($quantite);

        // Création de la commande spéciale
        $commandeSpeciale = new CommandeSpec();
        $commandeSpeciale->setCommande($commande)
                        ->setAttestationMedicale($newFilename)
                        ->setReponse(null);

        // Persiste les entités
        $em->persist($commande);
        $em->persist($commandeSpeciale);
        $em->flush();

        $this->addFlash('success', 'Votre commande a été enregistrée avec succès. Vous recevrez une réponse sur votre mail dans les prochaines 24 heures.');
    } catch (\Exception $e) {
        $this->addFlash('error', 'Une erreur est survenue lors de l\'enregistrement de la commande.');
    }

    return $this->redirectToRoute('app_client_medicament');
}

// #[Route('/client/profile', name: 'app_profile')]
// public function profile(){

//     $client = $this->getUser();
//     return $this->render('client/profile.html.twig', [
//         'client' => $client,

//     ]);

// }
#[Route('/client/profile', name: 'app_profile')]
public function edit(Request $request, EntityManagerInterface $entityManager): Response
{
    $client = $this->getUser();  // Récupère l'utilisateur connecté (ici un Client)

    $form = $this->createForm(ProfileType::class, $client);  // Crée le formulaire avec l'objet Client

    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        // Enregistrer les modifications
        $entityManager->flush();

        // Message de succès
        $this->addFlash('success', 'Profil mis à jour avec succès!');

        return $this->redirectToRoute('app_profile');  // Redirige vers la page de profil
    }

    return $this->render('client/profile.html.twig', [
        'form' => $form->createView()
    ]);
}


}