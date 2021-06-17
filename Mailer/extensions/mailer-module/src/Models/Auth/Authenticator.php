<?php
declare(strict_types=1);

namespace Remp\MailerModule\Models\Auth;

use Nette\Application\LinkGenerator;
use Nette\Http\IResponse;
use Nette\Security\AuthenticationException;
use Nette\Security\Authenticator as NetteAuthenticator;
use Nette\Security\IIdentity;
use Nette\Security\SimpleIdentity;

class Authenticator implements NetteAuthenticator
{
    /** @var RemoteUser */
    private $remoteUser;

    /** @var IResponse */
    private $response;

    /** @var LinkGenerator */
    private $linkGenerator;

    public function __construct(
        RemoteUser $remoteUser,
        IResponse $response,
        LinkGenerator $linkGenerator
    ) {
        $this->remoteUser = $remoteUser;
        $this->response = $response;
        $this->linkGenerator = $linkGenerator;
    }

    public function authenticate(string $user, string $password): IIdentity
    {
        if ($user === "" && $password === "") {
            $link = $this->linkGenerator->link('Mailer:Sign:In');
            $this->response->redirect($link);
            exit();
        }

        $result = $this->remoteUser->remoteLogin($user, $password);
        if ($result['status'] === 'error') {
            throw new AuthenticationException($result['message']);
        }

        $user = $result['data']['user'];

        return new SimpleIdentity($user['id'], 'admin', [
            'email' => $user['email'],
            'token' => $result['data']['access']['token'],
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name']
        ]);
    }
}
