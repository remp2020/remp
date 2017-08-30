# Nette SSO connector

## Installation

To include the SSO connector within the project, update your `composer.json` file accordingly:

```json
{
    "minimum-stability": "dev",
    "prefer-stable": true,
    "repositories": [
        {
            "type": "path",
            "url": "../Composer/nette-sso"
        }
    ],
    "require": {
        // ... 
        "remp/nette-sso": "*"
    }
}
```

Include the service providers within your `config.neon`:
                
```php
parameters:
    sso_host: @environmentConfig::get('SSO_BASE_URL')
    sso_error_url: @environmentConfig::get('SSO_ERROR_URL')
    # ...
    
services:
    # ...
    - Remp\NetteSso\Security\Client(%sso_host%)
    authenticator:
        class: Remp\NetteSso\Security\Authenticator(%sso_error_url%)
    security.userStorage:
        class: Remp\NetteSso\Security\UserStorage
    # ...
```

To get user logged in, call following in your presenter or alter according to your scenario
outside of presenter.

```php
class SignPresenter extends \Nette\Application\UI\Presenter
{
    public function renderIn()
    {
        $identity = $this->getUser()->authenticator->authenticate([]);
        $this->getUser()->getStorage()->setIdentity($identity);
        $this->getUser()->getStorage()->setAuthenticated(true);
        
        $this->redirect('Dashboard:Default')
    }
}

```

## Accessing user

You can use `\Nette\Security\User` provided by `\Nette\Application\UI\Presenter`.

```php
$this->getUser->getIdentity() // returns configured implementation of \Nette\Security\IIdentity
$this->getUser()->getId() // returns current user ID
$this->getUser()->isLoggedIn() // checks if user is logged in
```

## Configuration

You should configure SSO connector via `config.neon`. See example above to get the list of configurable parameters.