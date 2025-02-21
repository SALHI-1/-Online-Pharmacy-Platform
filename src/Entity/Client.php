<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity]
#[ORM\Table(name: "client")]
class Client implements UserInterface , PasswordAuthenticatedUserInterface
{

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $email;

    #[ORM\Column(length: 100)]
    private ?string $nom = null;

    #[ORM\Column(length: 100)]
    private ?string $prenom = null;

    #[ORM\Column(type: "string")]
    private ?string $password;


    #[ORM\Column(length: 255)]
    private ?string $ville = null;

    #[ORM\Column(length: 255)]
    private ?string $adresse = null; // Correction du nom de la variable (adress -> adresse)

    #[ORM\Column(length: 20)]
    private ?string $telephone = null;

    #[ORM\Column(length: 10, unique: true)]
    private ?string $cin = null; // Correction du nom (CIN -> cin) pour respecter les conventions


     // Getters
     public function getId(): ?int
     {
         return $this->id;
     }
 
     public function getEmail(): ?string
     {
         return $this->email;
     }
 
     public function getNom(): ?string
     {
         return $this->nom;
     }
 
     public function getPrenom(): ?string
     {
         return $this->prenom;
     }
 
     // Setters
     public function setEmail(string $email): self
     {
         $this->email = $email;
         return $this;
     }
 
     public function setNom(string $nom): self
     {
         $this->nom = $nom;
         return $this;
     }
 
     public function setPrenom(string $prenom): self
     {
         $this->prenom = $prenom;
         return $this;
     }


    // Getters et Setters
    public function getVille(): ?string
    {
        return $this->ville;
    }

    public function setVille(string $ville): self
    {
        $this->ville = $ville;
        return $this;
    }

    public function getAdresse(): ?string
    {
        return $this->adresse;
    }

    public function setAdresse(string $adresse): self
    {
        $this->adresse = $adresse;
        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(string $telephone): self
    {
        $this->telephone = $telephone;
        return $this;
    }

    public function getCin(): ?string
    {
        return $this->cin;
    }

    public function setCin(string $cin): self
    {
        $this->cin = $cin;
        return $this;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function getRoles(): array
    {
        return ['ROLE_CLIENT'];
    }

public function eraseCredentials(): void
    {
        // Si vous stockez des données sensibles temporaires
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }
    
    public function getPassword(): ?string
    {
        return $this->password;
    }
}
