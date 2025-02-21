<?php
namespace App\Service;

use Symfony\Component\HttpFoundation\RequestStack;
use App\Repository\MedicamentRepository;

class PanierService
{
    private MedicamentRepository $medicamentRepository;
    private RequestStack $requestStack;

    public function __construct(RequestStack $requestStack, MedicamentRepository $medicamentRepository)
    {
        $this->requestStack = $requestStack;
        $this->medicamentRepository = $medicamentRepository;
    }

    private function getSession()
    {
        return $this->requestStack->getSession();
    }

    /**
     * Ajouter un médicament au panier
     */
    public function ajouter(int $id)
    {
        $session = $this->getSession();
        $panier = $session->get('panier', []);

        if (!isset($panier[$id])) {
            $panier[$id] = 1;
        } else {
            $panier[$id]++;
        }

        $session->set('panier', $panier);
    }

    /**
     * Supprimer un médicament du panier (réduire la quantité)
     */
    public function supprimer(int $id)
    {
        $session = $this->getSession();
        $panier = $session->get('panier', []);

        if (isset($panier[$id])) {
            if ($panier[$id] > 1) {
                $panier[$id]--;
            } else {
                unset($panier[$id]); // Supprime l'élément si la quantité atteint 0
            }
        }

        $session->set('panier', $panier);
    }

    /**
     * Supprimer complètement un médicament du panier
     */
    public function supprimerCompletement(int $id)
    {
        $session = $this->getSession();
        $panier = $session->get('panier', []);

        unset($panier[$id]);

        $session->set('panier', $panier);
    }

    /**
     * Vider entièrement le panier
     */
    public function vider()
    {
        $session = $this->getSession();
        $session->remove('panier');
    }

    /**
     * Récupérer le contenu du panier avec les détails des médicaments
     */
    public function getPanier()
    {
        $session = $this->getSession();
        $panier = $session->get('panier', []);
        $panierData = [];
        $total = 0;

        foreach ($panier as $id => $quantite) {
            $medicament = $this->medicamentRepository->find($id);
            if ($medicament) {
                $panierData[] = [
                    'medicament' => $medicament,
                    'quantite' => $quantite,
                ];
                $total += $medicament->getPrix() * $quantite;
            }
        }

        return [
            'items' => $panierData,
            'total' => $total,
        ];
    }
    public function confirmerAchat(CommandeService $commandeService): string
{
    $panier = $this->getSession()->get('panier', []);

    if (empty($panier)) {
        throw new \Exception("Le panier est vide.");
    }

    $pdfPath = $commandeService->enregistrerCommande($panier);
    $this->getSession()->remove('panier'); // Vider le panier après l'achat

    return $pdfPath;
}

}
