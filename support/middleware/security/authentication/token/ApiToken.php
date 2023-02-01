<?php

namespace support\middleware\security\authentication\token;

use Biz\User\CurrentUser;
use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;

class ApiToken  extends AbstractToken
{
    private $credentials;
    private $providerKey;

    public function __construct(CurrentUser $user, array $roles = array())
    {
        parent::__construct($roles);
        $this->setUser($user);
        parent::setAuthenticated($user->isLogin());
    }

    /**
     * {@inheritdoc}
     */
    public function setAuthenticated($isAuthenticated)
    {
        if ($isAuthenticated) {
            throw new \LogicException('Cannot set this token to trusted after instantiation.');
        }

        parent::setAuthenticated(false);
    }

    /**
     * {@inheritdoc}
     */
    public function getCredentials()
    {
        return $this->credentials;
    }

    /**
     * Returns the provider key.
     *
     * @return string The provider key
     */
    public function getProviderKey()
    {
        return $this->providerKey;
    }

    /**
     * {@inheritdoc}
     */
    public function eraseCredentials()
    {
        parent::eraseCredentials();

        $this->credentials = null;
    }
}