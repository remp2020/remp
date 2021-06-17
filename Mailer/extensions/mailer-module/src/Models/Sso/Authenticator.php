<?php
declare(strict_types=1);

namespace Remp\MailerModule\Models\Sso;

use Nette\Http\Url;
use Nette\Http\IRequest;
use Nette\Http\IResponse;
use Nette\Security\IIdentity;
use Nette\Security\Identity;

class Authenticator implements \Nette\Security\Authenticator
{
    private $client;

    private $request;

    private $response;

    private $errorUrl;

    public function __construct($errorUrl, Client $client, IRequest $request, IResponse $response)
    {
        $this->client = $client;
        $this->request = $request;
        $this->response = $response;
        $this->errorUrl = $errorUrl;
    }


    /**
     * @param string $user
     * @param string $password
     *
     * @return IIdentity
     * @throws \Nette\Security\AuthenticationException
     */
    public function authenticate(string $user, string $password): IIdentity
    {
        $token = $this->request->getQuery('token');
        try {
            $result = $this->client->introspect($token);
        } catch (SsoExpiredException $e) {
            $redirectUrl = new Url($e->redirect);
            $redirectUrl->setQueryParameter('successUrl', $this->request->getUrl()->getAbsoluteUrl());
            $redirectUrl->setQueryParameter('errorUrl', $this->errorUrl);
            $this->response->redirect($redirectUrl->getAbsoluteUrl());
            exit;
        }

        return new Identity(
            $result['id'],
            $result['scopes'],
            [
                'email' => $result['email'],
                'name' => $result['name'],
                'token' => $token,
            ]
        );
    }
}
