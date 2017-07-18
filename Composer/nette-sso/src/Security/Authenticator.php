<?php

namespace Remp\NetteSso\Security;

use Nette\Http\UrlScript;
use Nette\Security\IAuthenticator;
use Nette\Http\IRequest;
use Nette\Http\IResponse;
use Nette\Security\Identity;

class Authenticator implements IAuthenticator
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
     * @param array $credentials Only present due to interface limitations, please use empty array.
     * @return Identity
     */
    public function authenticate(array $credentials)
    {
        $token = $this->request->getQuery('token');
        try {
            $result = $this->client->introspect($token);
        } catch (SsoExpiredException $e) {
            $redirectUrl = new UrlScript($e->redirect);
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
