<?php
declare(strict_types=1);

namespace Remp\MailerModule\Models\Auth;

use Nette\Application\LinkGenerator;
use Nette\Http\IResponse;
use Nette\Security\AuthenticationException;
use Nette\Security\IIdentity;
use Nette\Security\SimpleIdentity;

class SimpleAuthenticator implements \Nette\Security\Authenticator
{
    /** @var IResponse */
    private $response;

    /** @var LinkGenerator */
    private $linkGenerator;

    private $users = [];

    public function __construct(
        IResponse $response,
        LinkGenerator $linkGenerator
    ) {
        $this->response = $response;
        $this->linkGenerator = $linkGenerator;
    }

    public function addUser(string $email, string $password)
    {
        $this->users[] = [
            'email' => $email,
            'password' => $password,
        ];
    }

    public function authenticate(string $user, string $password): IIdentity
    {
        if ($user === "" && $password === "") {
            $link = $this->linkGenerator->link('Mailer:Sign:In');
            $this->response->redirect($link);
            exit();
        }

        foreach ($this->users as $id => $item) {
            if ($item['email'] === $user && hash_equals($password, $item['password'])) {
                $token = bin2hex(random_bytes(16));
                return new SimpleIdentity($id, 'admin', [
                    'email' => $item['email'],
                    'token' => $token,
                    'first_name' => '',
                    'last_name' => ''
                ]);
            }
        }

        throw new AuthenticationException('Wrong email or password.');
    }
}
