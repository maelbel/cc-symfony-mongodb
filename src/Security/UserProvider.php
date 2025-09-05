<?php

namespace App\Security;

use App\Document\Customer;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class UserProvider implements UserProviderInterface, PasswordUpgraderInterface
{

    public function __construct(
        private DocumentManager $dm,
    ){
    }

    /**
     * Symfony calls this method if you use features like switch_user
     * or remember_me.
     *
     * If you're not using these features, you do not need to implement
     * this method.
     *
     * @throws UserNotFoundException if the user is not found
     */
    public function loadUserByIdentifier($identifier): UserInterface
    {
        $repo = $this->dm->getRepository(Customer::class);
        // Load a User object from your data source or throw UserNotFoundException.
        // The $identifier argument may not actually be a username:
        // it is whatever value is being returned by the getUserIdentifier()
        // method in your User class.
        $user = $repo->findOneBy(['username' => $identifier]);

        if (!$user instanceof Customer){
            throw new UserNotFoundException('User '.$identifier.' not found.');
        }

        return $user;
    }

    /**
     * @deprecated since Symfony 5.3, loadUserByIdentifier() is used instead
     */
    public function loadUserByUsername($username): UserInterface
    {
        return $this->loadUserByIdentifier($username);
    }

    /**
     * Refreshes the user after being reloaded from the session.
     *
     * When a user is logged in, at the beginning of each request, the
     * User object is loaded from the session and then this method is
     * called. Your job is to make sure the user's data is still fresh by,
     * for example, re-querying for fresh User data.
     *
     * If your firewall is "stateless: true" (for a pure API), this
     * method is not called.
     */
    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof Customer) {
            throw new UnsupportedUserException(sprintf('Invalid user class "%s".', $user::class));
        }

        $repo = $this->dm->getRepository(Customer::class);

        $userReloaded = $repo->find($user->getCustomerCode());

        if (!$userReloaded instanceof Customer) {
            throw new UserNotFoundException('User not found during refresh');
        }
    
        return $userReloaded;
    }

    /**
     * Tells Symfony to use this provider for this User class.
     */
    public function supportsClass(string $class): bool
    {
        return Customer::class === $class || is_subclass_of($class, Customer::class);
    }

    /**
     * Upgrades the hashed password of a user, typically for using a better hash algorithm.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof Customer) {
            throw new UnsupportedUserException('Instances of'.$user::class.' are not supported');
        }

        $user->setPassword($newHashedPassword);
        $this->dm->persist($user);
        $this->dm->flush();
    }
}
