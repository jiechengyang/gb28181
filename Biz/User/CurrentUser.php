<?php


namespace Biz\User;


use support\exception\UnexpectedValueException;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @method string getUserIdentifier()
 */
class CurrentUser implements UserInterface, EquatableInterface, \ArrayAccess, \Serializable
{
    protected $data = [];
    protected $permissions = [];
    protected $context = [];

    public function offsetExists($offset)
    {
        return $this->__isset($offset);
    }

    public function offsetGet($offset)
    {
        return $this->__get($offset);
    }

    public function offsetSet($offset, $value)
    {
        return $this->__set($offset, $value);
    }

    public function offsetUnset($offset)
    {
        return $this->__unset($offset);
    }

    public function serialize()
    {
        return serialize($this->data);
    }

    public function unserialize($serialized)
    {
        $this->data = unserialize($serialized);
    }

    public function __set($name, $value)
    {
        $this->data[$name] = $value;

        return $this;
    }

    public function __get($name)
    {
        if (array_key_exists($name, $this->data)) {
            return $this->data[$name];
        }
        throw new UnexpectedValueException("{$name} is not exist in CurrentUser.");
    }

    public function __isset($name)
    {
        return isset($this->data[$name]);
    }

    public function __unset($name)
    {
        unset($this->data[$name]);
    }

    public function fromArray(array $user)
    {
        $this->data = $user;

        return $this;
    }

    public function isEqualTo(UserInterface $user)
    {
        if ($this->email !== $user->getUsername()) {
            return false;
        }

        if (array_diff($this->roles, $user->getRoles())) {
            return false;
        }

        if (array_diff($user->getRoles(), $this->roles)) {
            return false;
        }

        return true;
    }

    public function getRoles()
    {
        return $this->roles;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function getSalt()
    {
        return $this->salt;
    }

    public function getUsername()
    {
        return $this->email;
    }

    public function getId()
    {
        return $this->id;
    }

    public function isLogin()
    {
        return !empty($this->id);
    }

    public function isSuperAdmin()
    {
        return count(array_intersect($this->getRoles(), ['ROLE_SUPER_ADMIN'])) > 0;
    }

    public function eraseCredentials()
    {
        // TODO: Implement eraseCredentials() method.
    }

    public function toArray()
    {
        return $this->data;
    }

    public function setPermissions($permissions)
    {
        $this->permissions = $permissions;

        return $this;
    }

    public function getPermissions()
    {
        return $this->permissions;
    }

    public function setContext($name, $value)
    {
        $this->context[$name] = $value;
    }

    public function getContext($name)
    {
        return $this->context[$name] ?? null;
    }
}