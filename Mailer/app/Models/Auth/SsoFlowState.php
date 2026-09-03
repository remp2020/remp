<?php
declare(strict_types=1);

namespace Remp\Mailer\Models\Auth;

use Nette\Http\Session;

class SsoFlowState
{
    private const SESSION_SECTION = 'crm_sso';

    public function __construct(private readonly Session $session)
    {
    }

    /**
     * Call when initiating SSO login.
     * @param string $destinationUrl
     *
     * @return string
     * @throws \Random\RandomException
     */
    public function start(string $destinationUrl): string
    {
        $state = bin2hex(random_bytes(16));

        $section = $this->session->getSection(self::SESSION_SECTION);
        $section->set('state', $state);
        $section->set('destination', $destinationUrl);

        return $state;
    }


    /**
     * Call when returning back from the SSO login
     *
     * @return string|null returns either destinationUrl (in case of valid state)
     *                     or null, meaning state is not verified. This should be treated
     *                     as a failed login.
     */
    public function consume(?string $state): ?string
    {
        $section = $this->session->getSection(self::SESSION_SECTION);
        $expected = $section->get('state');
        $destinationUrl = $section->get('destination');
        $section->remove();

        if ($state === null || !is_string($expected) || !is_string($destinationUrl)) {
            return null;
        }

        return hash_equals($expected, $state) ? $destinationUrl : null;
    }
}
