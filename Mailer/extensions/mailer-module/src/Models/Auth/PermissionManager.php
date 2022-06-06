<?php
declare(strict_types=1);

namespace Remp\MailerModule\Models\Auth;

use Nette\Security\Permission;
use Nette\Security\User;

class PermissionManager
{
    private Permission $acl;

    private array $roles = [];

    private array $privileges = [];

    public function __construct()
    {
        $this->acl = new Permission();
    }

    /**
     * @param User $user
     * @param string $resource
     * @param string $privilege
     * @return bool
     */
    public function isAllowed(User $user, string $resource, string $privilege): bool
    {
        $email = $user->getIdentity()->getData()['email'];

        // if privilege is not registered for any role we want to allow action for backwards compatibility
        if (!isset($this->privileges[$resource][$privilege])) {
            return true;
        }

        if (empty($this->roles[$email])) {
            return false;
        }
        $userRoles = $this->roles[$email];

        foreach ($userRoles as $userRole) {
            if ($this->acl->isAllowed($userRole, $resource, $privilege)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $email
     * @param string $role
     */
    public function assignRole(string $email, string $role): void
    {
        if (!$this->acl->hasRole($role)) {
            $this->acl->addRole($role);
        }

        $this->roles[$email][] = $role;
    }

    /**
     * @param string $role
     * @param string $resource
     * @param string|string[] $privileges
     */
    public function allow(string $role, string $resource, $privileges): void
    {
        if (!$this->acl->hasRole($role)) {
            $this->acl->addRole($role);
        }

        if (!$this->acl->hasResource($resource)) {
            $this->acl->addResource($resource);
        }

        foreach ((array) $privileges as $privilege) {
            $this->privileges[$resource][$privilege] = true;
        }
        $this->acl->allow($role, $resource, $privileges);
    }
}
