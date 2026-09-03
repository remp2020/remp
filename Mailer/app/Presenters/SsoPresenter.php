<?php
declare(strict_types=1);

namespace Remp\Mailer\Presenters;

use Nette\Application\UI\Presenter;
use Nette\Security\SimpleIdentity;
use Remp\Mailer\Models\Auth\CrmSsoClient;
use Remp\Mailer\Models\Auth\CrmSsoException;
use Remp\Mailer\Models\Auth\SsoFlowState;

/**
 * Callback of the CRM SSO flow started by Remp\Mailer\Models\Auth\CrmSsoAuthenticator.
 */
final class SsoPresenter extends Presenter
{
    /**
     * $crmSsoClient is optional on purpose - presenters are auto-registered as services, so requiring it
     * would break the DI container on deployments that don't configure the CRM SSO login at all.
     */
    public function __construct(
        private readonly SsoFlowState $ssoFlowState,
        private readonly ?CrmSsoClient $crmSsoClient = null,
    ) {
        parent::__construct();
    }

    public function actionCallback(?string $code = null, ?string $state = null, ?string $error = null): void
    {
        if ($this->crmSsoClient === null) {
            $this->signInFailed('CRM SSO login is not configured. Have you add CrmSsoClient to the configuration?');
        }

        $destinationUrl = $this->ssoFlowState->consume($state);

        if ($error !== null) {
            $this->signInFailed('You are not authorized to access Mailer.');
        }

        if ($destinationUrl === null) {
            $this->signInFailed('Login request is no longer valid, please try again.');
        }

        if ($code === null) {
            $this->signInFailed('Login failed, please try again.');
        }

        try {
            $crmUser = $this->crmSsoClient->resolve($code);
        } catch (CrmSsoException $exception) {
            $this->signInFailed('Unable to verify the login with CRM.');
        }

        $roles = $crmUser['roles'] ?? [];
        if (!in_array('superadmin', $roles, true) && !in_array('remp/mailer', $roles, true)) {
            $email = $crmUser['email'];
            $this->signInFailed("Your CRM account $email is not authorized to access Mailer. Either log in with different user or request additional access.");
        }

        $this->getUser()->login(new SimpleIdentity($crmUser['id'], 'admin', [
            'email' => $crmUser['email'],
        ]));

        $this->redirectUrl($destinationUrl);
    }

    private function signInFailed(string $message): never
    {
        $this->redirect(':Mailer:Sign:error', ['error' => $message]);
    }
}
