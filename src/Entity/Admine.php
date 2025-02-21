<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

#[ORM\Entity]
#[ORM\Table(name: "admin")]

class Admine implements UserInterface ,PasswordAuthenticatedUserInterface
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

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }


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


     public function getRoles(): array
     {
         return ['ROLE_ADMIN']; // Rôle par défaut pour l'admin
     }
 
     public function getUserIdentifier(): string
     {
         return $this->email;
     }
 
     public function eraseCredentials(): void
     {
         // Si tu stockes des infos sensibles, nettoie-les ici.
     }
}
