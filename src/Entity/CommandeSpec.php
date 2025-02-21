<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class CommandeSpec
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: Commande::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Commande $commande = null;

    #[ORM\Column(type: 'text')]
    private ?string $attestationMedicale = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $reponse = null;

    // Getters et Setters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCommande(): ?Commande
    {
        return $this->commande;
    }

    public function setCommande(?Commande $commande): self
    {
        $this->commande = $commande;
        return $this;
    }

    public function getAttestationMedicale(): ?string
    {
        return $this->attestationMedicale;
    }

    public function setAttestationMedicale(string $attestationMedicale): self
    {
        $this->attestationMedicale = $attestationMedicale;
        return $this;
    }

    public function getReponse(): ?string
    {
        return $this->reponse;
    }

    public function setReponse(?string $reponse): self
{
    if ($reponse !== null && !in_array($reponse, ['Acceptée', 'Refusée'])) {
        throw new \InvalidArgumentException("Valeur reçue : '$reponse'. La réponse doit être 'Acceptée' ou 'Refusée'.");
    }

    $this->reponse = $reponse;
    return $this;
}



}
