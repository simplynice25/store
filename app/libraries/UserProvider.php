<?php

namespace libraries;

use Silex\Application;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\User;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;

class UserProvider implements UserProviderInterface
{
    private $app;
    
    public function __construct(Application $app) {
        $this->app = $app;
    }

    public function loadUserByUsername($username) {
        $user = $this->app['orm.em']->getRepository('models\User')->findOneBy(array('email' => $username, 'view_status' => 5));
        if (!is_object($user)) {
            throw new UsernameNotFoundException(sprintf('Username "%s" does not exist.', $username));
        }

        return new User($user->getEmail(), $user->getPassword(), explode(',', $user->getRoles()), true, true, true, true);
    }

    public function refreshUser(UserInterface $user) {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_class($user)));
        }
 
        return $this->loadUserByUsername($user->getUsername());
    }
 
    public function supportsClass($class) {
        return $class === 'Symfony\Component\Security\Core\User\User';
    }
}