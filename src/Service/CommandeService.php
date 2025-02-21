<?php

namespace App\Service;

use App\Entity\Client;
use App\Entity\Commande;
use App\Entity\Medicament;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\SecurityBundle\Security;

class CommandeService
{
    private EntityManagerInterface $entityManager;
    private Security $security;

    public function __construct(EntityManagerInterface $entityManager, Security $security)
    {
        $this->entityManager = $entityManager;
        $this->security = $security;
    }

    public function enregistrerCommande(array $panier): string
    {
        $user = $this->security->getUser();
        if (!$user) {
            throw new \Exception("Utilisateur non authentifié.");
        }

        $commandes = [];

        foreach ($panier as $id => $quantite) {
            $medicament = $this->entityManager->getRepository(Medicament::class)->find($id);

            if ($medicament && $medicament->getType() === 'En vente libre') {
                $commande = new Commande();
                $commande->setClient($user);
                $commande->setMedicament($medicament);
                $commande->setQuantite($quantite);
                $commande->setStatut("En cours");
                $commande->setDate(new \DateTime());

                $this->entityManager->persist($commande);
                $commandes[] = $commande;
            }
        }

        $this->entityManager->flush();

        return $this->genererRecuPDF($user, $commandes);
    }

    
    
    private function genererRecuPDF(Client $user, array $commandes): string
    {
        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $options->set('isHtml5ParserEnabled', true);
        $dompdf = new Dompdf($options);
    
        // Informations de la pharmacie
        $pharmacieNom = "Pharmacie SantéPlus";
        $pharmacieAdresse = "123 Avenue de la Santé, Tanger, Maroc";
        $pharmacieTelephone = "+212 5 39 12 34 56";
        $delaiLivraison = "Livraison sous 48h maximum";
    
        // Début du contenu HTML
        $html = '<!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; color: #333; }
                .header { text-align: center; margin-bottom: 20px; }
                .header img { width: 100px; }
                .header h2 { color: #009879; }
                .info { margin-bottom: 20px; }
                .info p { margin: 5px 0; }
                .highlight { color: #009879; font-weight: bold; }
                .table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                .table th, .table td { border: 1px solid #ddd; padding: 8px; text-align: center; }
                .table th { background-color: #009879; color: white; }
                .total { text-align: right; font-size: 18px; font-weight: bold; margin-top: 20px; }
                .footer { text-align: center; margin-top: 30px; font-size: 12px; color: #777; }
            </style>
        </head>
        <body>
            <div class="header">
                <h2>'.$pharmacieNom.'</h2>
                <p>'.$pharmacieAdresse.' |  '.$pharmacieTelephone.'</p>
            </div>
    
            <div class="info">
                <p><span class="highlight">Client :</span> '.$user->getNom().' '.$user->getPrenom().'</p>
                <p><span class="highlight">Email :</span> '.$user->getEmail().'</p>
                <p><span class="highlight">Téléphone :</span> '.$user->getTelephone().'</p>
                <p><span class="highlight">Adresse :</span> '.$user->getAdresse().', '.$user->getVille().'</p>
                <p><span class="highlight">CIN :</span> '.$user->getCin().'</p>
                <hr>
                <p><span class="highlight">Délai de livraison :</span> '.$delaiLivraison.'</p>
            </div>
    
            <table class="table">
                <tr>
                    <th>Médicament</th>
                    <th>Quantité</th>
                    <th>Prix Unitaire (€)</th>
                    <th>Total (€)</th>
                </tr>';
    
        $totalCommande = 0;
        foreach ($commandes as $commande) {
            $nomMedicament = $commande->getMedicament()->getNom();
            $quantite = $commande->getQuantite();
            $prixUnitaire = $commande->getMedicament()->getPrix();
            $prixTotal = $prixUnitaire * $quantite;
            $totalCommande += $prixTotal;
    
            $html .= "<tr>
                        <td>$nomMedicament</td>
                        <td>$quantite</td>
                        <td>$prixUnitaire</td>
                        <td>$prixTotal</td>
                      </tr>";
        }
    
        $html .= '</table>
            <p class="total">Total de la commande : <span class="highlight">'.$totalCommande.' €</span></p>
    
            <div class="footer">
                <p>Merci pour votre confiance. Pour toute question, contactez-nous au  '.$pharmacieTelephone.'</p>
            </div>
        </body>
        </html>';
    
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
    
        $pdfPath = 'uploads/recu/recu_' . $user->getId() . '_' . time() . '.pdf';
        file_put_contents($pdfPath, $dompdf->output());
    
        return $pdfPath;
    }
    
}
