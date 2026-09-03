<?php
declare(strict_types=1);

namespace Remp\Mailer\Models\Auth;

use Nette\Application\LinkGenerator;
use Nette\Http\IRequest;
use Nette\Http\IResponse;
use Nette\Http\Url;
use Nette\Security\IIdentity;

class CrmSsoAuthenticator implements \Nette\Security\Authenticator
{
    public function __construct(
        private readonly string $clientId,
        private readonly string $crmAddr,
        private readonly SsoFlowState $ssoFlowState,
        private readonly IRequest $request,
        private readonly IResponse $response,
        private readonly LinkGenerator $linkGenerator,
    ) {
    }

    /**
     * BasePresenter::startup() calls login("", "") on every request of a logged-out user, so both
     * credentials are always empty and unused.
     */
    public function authenticate(string $username, string $password): IIdentity
    {
        $state = $this->ssoFlowState->start($this->request->getUrl()->getAbsoluteUrl());

        $url = new Url(rtrim($this->crmAddr, '/') . '/sso/authorize');
        $url->setQueryParameter('client_id', $this->clientId)
            ->setQueryParameter('redirect_uri', $this->linkGenerator->link('Crm:Sso:callback'))
            ->setQueryParameter('state', $state);

        $this->response->redirect($url->getAbsoluteUrl());
        exit;
    }
}
