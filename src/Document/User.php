<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ODM\Document]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ODM\Id]
    private $id;

    #[ODM\Field(type: 'string')]
    private $email;

    #[ODM\Field(type: 'string')]
    private $password;

    #[ODM\Field(type: 'collection')]
    private $roles = [];

    public function getId(): ?string { return $this->id; }

    public function getEmail(): ?string { return $this->email; }
    public function setEmail(string $email): self { $this->email = $email; return $this; }

    public function getUserIdentifier(): string { return $this->email; }
    public function getUsername(): string { return $this->email; }

    public function getPassword(): string { return $this->password; }
    public function setPassword(string $password): self { $this->password = $password; return $this; }

    public function getRoles(): array { return $this->roles ?: ['ROLE_USER']; }
    public function setRoles(array $roles): self { $this->roles = $roles; return $this; }

    public function eraseCredentials() : void {}
}
