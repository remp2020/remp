<?php
declare(strict_types=1);

namespace Remp\MailerModule\Models\Auth;

/**
 * Implemented by authenticators that delegate the login to an external identity provider (e.g. CRM),
 * so that the sign-in error page can offer "sign out of the provider and log in with a different account".
 */
interface SignOutUrlProviderInterface
{
    /**
     * Returns URL which signs the user out of the identity provider and then redirects them to $redirectUrl.
     */
    public function getSignOutUrl(string $redirectUrl): string;
}
