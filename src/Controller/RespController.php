<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\MedicamentRepository;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Entity\Commande;
use App\Entity\CommandeSpec;
use Exception;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
final class RespController extends AbstractController
{


    // #[Route('/admin/login', name: 'login')]
    // public function index(): Response
    // {
    //     return $this->render('home/index.html.twig');
    // }

    #[Route('/admin/dashbord', name: 'app_dashbord')]
    public function dashbord(): Response
    {
        return $this->render('home/dashbord.html.twig');
    }


    #[Route('/admin/medicament', name: 'app_medicament')]

    public function index(Request $request, MedicamentRepository $medicamentRepository): Response
    {
        $searchTerm = $request->query->get('search', '');
        
        if ($searchTerm) {
            $medicaments = $medicamentRepository->findByNom($searchTerm);
        } else {
            $medicaments = $medicamentRepository->findAll();
        }

        return $this->render('home/medicament.html.twig', [
            'medicaments' => $medicaments,
            'searchTerm' => $searchTerm
        ]);
    }
    
    
    #[Route('/admin/commande', name: 'app_commande')]
public function commandes(Request $request, PaginatorInterface $paginator ,EntityManagerInterface $entityManager): Response
{
    $type = $request->query->get('type');
    $date = $request->query->get('date') ? new \DateTime($request->query->get('date')) : null;

    $qb = $entityManager->createQueryBuilder()
        ->select('c, m')
        ->from('App\Entity\Commande', 'c')
        ->join('c.medicament', 'm')
        ->orderBy('c.date', 'DESC');

    if ($type) {
        $qb->andWhere('m.type = :type')->setParameter('type', $type);
    }

    if ($date) {
        $qb->andWhere('c.date BETWEEN :start AND :end')
           ->setParameter('start', $date->format('Y-m-d 00:00:00'))
           ->setParameter('end', $date->format('Y-m-d 23:59:59'));
    }

    $commandes = $qb->getQuery()->getResult();
    $pagination = $paginator->paginate(
        $qb->getQuery(),
        $request->query->getInt('page', 1), // Page actuelle
        6 // Nombre d'éléments par page
    );

    return $this->render('home/commande.html.twig', [
        
        'type' => $type,
        'date' => $date ? $date->format('Y-m-d') : '','commandes' => $pagination,
    ]);
}

#[Route('/admin/commande/update-statut', name: 'app_commande_update_statut', methods: ['POST'])]
public function updateStatut(Request $request, EntityManagerInterface $entityManager): JsonResponse
{
    // Vérifier le token CSRF
    if (!$this->isCsrfTokenValid('update-statut', $request->headers->get('X-CSRF-TOKEN'))) {
        return new JsonResponse(['success' => false, 'message' => 'Token CSRF invalide'], 400);
    }

    $data = json_decode($request->getContent(), true);

    if (!isset($data['id'], $data['statut'])) {
        return new JsonResponse(['success' => false, 'message' => 'Données invalides'], 400);
    }

    $commande = $entityManager->getRepository(Commande::class)->find($data['id']);
    if (!$commande) {
        return new JsonResponse(['success' => false, 'message' => 'Commande non trouvée'], 404);
    }

    try {
        $commande->setStatut($data['statut']);
        $entityManager->flush();
        return new JsonResponse(['success' => true]);
    } catch (\Exception $e) {
        return new JsonResponse(['success' => false, 'message' => 'Erreur lors de la mise à jour'], 500);
    }
}

#[Route('/admin/commandes-speciales', name: 'app_commandes_speciales')]
public function commandesSpeciales(EntityManagerInterface $em): Response
{
    $commandesSpeciales = $em->getRepository(CommandeSpec::class)->findBy(['reponse' => null]);

    return $this->render('home/commandes_spec.html.twig', [
        'commandesSpeciales' => $commandesSpeciales,
    ]);
}


#[Route('/admin/commande/{id}/{action}', name: 'admin_gerer_commande', methods: ['POST'])]
public function gererCommande(CommandeSpec $commandeSpec, string $action, EntityManagerInterface $em, MailerInterface $mailer): Response
{
    if (!in_array($action, ['accepter', 'refuser'])) {
        throw $this->createNotFoundException('Action invalide.');
    }

    $reponse = ($action === 'accepter') ? 'Acceptée' : 'Refusée';
    $commandeSpec->setReponse($reponse);
    $em->flush();

    // Envoi de l'email au client
    $client = $commandeSpec->getCommande()->getClient();
    $medicament = $commandeSpec->getCommande()->getMedicament();

    $email = (new Email())
        ->from('admin@pharmacie.com')
        ->to($client->getEmail())
        ->subject("Commande $reponse")
        ->text("Votre commande de {$medicament->getNom()} a été $reponse.");

    $mailer->send($email);

    return $this->redirectToRoute('app_commandes_speciales');
}



}
