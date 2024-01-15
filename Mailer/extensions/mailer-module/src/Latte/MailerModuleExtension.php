<?php
declare(strict_types=1);

namespace Remp\MailerModule\Latte;

use Latte\Extension;
use Remp\MailerModule\Models\Auth\PermissionManager;

class MailerModuleExtension extends Extension
{
    public function __construct(private PermissionManager $permissionManager)
    {
    }

    public function getTags(): array
    {
        return [
            'ifAllowed' => [IfAllowedNode::class, 'create'],
        ];
    }

    public function getProviders(): array
    {
        return [
            'permissionManager' => $this->permissionManager,
        ];
    }
}
