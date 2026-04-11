<?php

namespace App\Security;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class AdminUserProvider implements UserProviderInterface
{
    public function __construct(
        #[Autowire('%env(string:ADMIN_USERNAME)%')]
        private readonly string $username,
        #[Autowire('%env(string:ADMIN_PASSWORD_HASH)%')]
        private readonly string $passwordHash,
    ) {
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        if ($identifier !== $this->username) {
            $exception = new UserNotFoundException(sprintf('Admin user "%s" was not found.', $identifier));
            $exception->setUserIdentifier($identifier);

            throw $exception;
        }

        return $this->createAdminUser();
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$this->supportsClass($user::class)) {
            throw new UnsupportedUserException(sprintf('Unsupported user class "%s".', $user::class));
        }

        return $this->createAdminUser();
    }

    public function supportsClass(string $class): bool
    {
        return is_a($class, InMemoryUser::class, true);
    }

    private function createAdminUser(): InMemoryUser
    {
        return new InMemoryUser($this->username, $this->passwordHash, ['ROLE_ADMIN']);
    }
}
