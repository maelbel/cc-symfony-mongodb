<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ODM\Document(collection: 'customers')]
class Customer implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ODM\Id(strategy: 'INCREMENT', type: 'int')]
    private $customerCode;

    /**
     * @var list<string> The user roles
     */
    #[ODM\Field(type: 'collection')]
    private $roles = [];

    /**
     * @var string The hashed password
     */
    #[ODM\Field(type: 'string')]
    private $password;

    #[ODM\Field(type: 'string')]
    private $username;

    #[ODM\Field(type: 'string')]
    private $adress;

    #[ODM\Field(type: 'string')]
    private $mail;

    #[ODM\Field(type: 'int')]
    private $tel;

    public function getCustomerCode(): ?string
    {
        return $this->customerCode;
    }

    public function setCustomerCode(string $customerCode): static
    {
        $this->customerCode = $customerCode;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->customerCode;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }
  
    public function getAdress()
    {
        return $this->adress;
    }

    public function setAdress($adress)
    {
        $this->adress = $adress;

        return $this;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    public function getMail()
    {
        return $this->mail;
    }

    public function setMail($mail)
    {
        $this->mail = $mail;

        return $this;
    }

    public function getTel()
    {
        return $this->tel;
    }

    public function setTel($tel)
    {
        $this->tel = $tel;

        return $this;
    }

    /**
     * Ensure the session doesn't contain actual password hashes by CRC32C-hashing them, as supported since Symfony 7.3.
     */
    public function __serialize(): array
    {
        $data = (array) $this;
        $data["\0".self::class."\0password"] = hash('crc32c', $this->password);

        return $data;
    }

    #[\Deprecated]
    public function eraseCredentials(): void
    {
        // @deprecated, to be removed when upgrading to Symfony 8
    }
}
