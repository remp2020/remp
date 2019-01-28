# Mailer

## Admin (Nette)

Mailer Admin serves as a tool for configuration of mailers, creation of email layouts and
templates, and configuring and sending mail jobs to selected segments of users.

When the backend is ready, don't forget to install dependencies and run DB migrations:

```bash
# 1. Download PHP dependencies
composer install

# 2. Download JS/HTML dependencies
yarn install

# !. use extra switch if your system doesn't support symlinks (Windows; can be enabled)
yarn install --no-bin-links

# 3. Generate assets
yarn run dev // or any other alternative defined within package.json

# 4. Run migrations
php bin/command.php migrate:migrate

# 5. Run seeders
php bin/command.php db:seed
php bin/command.php demo:seed # optional
```

You can override any default config from
[`config.neon`](./app/config/config.neon) by creating file 
`config.local.neon` and setting your own values.

#### Dependencies

- PHP 7.1
- MySQL 5.7
- Redis 3.2

### Integration with user base

Mailer is dependent on external user base and segment provider. After the installation the application uses
dummy implementations `Remp\MailerModule\Segment\Dummy` and `Remp\MailerModule\User\Dummy`.

To integrate with Mailer you need to provide real implementation of these interfaces against your system
responsible for keeping user information. The API definition can be anything that suits you,
but in the end the implementation has to process the response and return the data in the structure
that's described below. 

#### Segment integration

To determine who to send an email to, Mailer is dependent on user segments - effectively lists of user IDs
which should receive a newsletter. You can register as many segment providers as you want, the only condition
is that the providers should work with the same user-base (one user ID has to always point to the) same user.

The implementation is required to implement [`Remp\MailerModule\Segments\ISegment`](app/models/Segments/ISegment.php)
interface.

There are three methods to implement:

* `provider(): string`: Uniquely identifies segment provider among other segment providers.
This is internally required to namespace segment names in case of same segment name being used in multiple
segment sources.
    ```php
    return "my-provider"; 
    ```
* `list(): array`: Returns list of all segments available for this provider. The structure
of response is:
    ```php
    return [
        [
            'name' => String, // user friendly label
            'provider' => String, // should be same as result of provider()
            'code' => String, // machine friendly name, slug
            'group' => [
                'id' => Integer, // ID of segment group
                'name' => String, // user friendly label of group
                'sorting' => Integer // sorting index; lower the number, sooner the group appears in the list 
            ]
        ],
    ];
    ``` 
* `users($segment): array`: Returns list of user IDs belonging to the segment.
  * `$segment`: Identification of requested segment.
    ```php
    [
        'provider' => String, // identification of segment provider 
        'code' => String, // segment code
    ]
    ```
  The response is than expected to be array of integers/strings representing user IDs:
  ```php
  return [
      Integer,
      Integer,
      // ...
  ]
  ```

##### Dummy implementation

See the [`Remp\MailerModule\Segments\Dummy`](app/models/Segments/Dummy.php) implementation as a reference
example.

##### REMP CRM implementation

See the [`Remp\MailerModule\Users\Segment`](app/models/Users/Segment.php) implementation to check how you can
initialize your class the dependencies, structure the request and process the result

The constructor accept two parameters. They should come from `app/config/config.local.neon` file:

```neon
parameters: 
    crm:
        addr: @environmentConfig::get('CRM_ADDR')
        api_token: @environmentConfig::get('CRM_API_TOKEN')

services:
	# add your implementation
	- Remp\MailerModule\Segment\Crm(%crm.addr%, %crm.api_token%)
```

#### User integration

As segments are working only with user IDs, and some of them might not be valid or active anymore, Mailer
requires an implementation that returns user information based on the ID.

The implementation is required to implement [`Remp\MailerModule\Users\IUser`](app/models/Users/IUser.php)
interface.

* `list($userIds, $page): array`: Returns the user information (primarily email address) for requested users based on
provided user IDs and pagination parameter. The pagination implementation on your side is not mandatory,
however strongly recommended.
  * `$userIds`: `[String|Integer, String|Integer, ...]` // List of user IDs; empty array should be handled as request for all users.
  * `$page`: `Integer`: Currently requested page.

  Response is then expected as follows:  

  ```php
  return [
      $userId => [
          'id' => String, // userId
          'email' => String, // valid email address of user
      ],
  ];
  ```

##### Dummy implementation

See the [`Remp\MailerModule\Users\Dummy`](app/models/Users/Dummy.php) implementation as a reference example.

##### REMP CRM implementation

See the [`Remp\MailerModule\Users\Crm`](app/models/Users/Crm.php) implementation to check how you can
initialize your class the dependencies, structure the request and process the result

The constructor accept two parameters. They should come from `app/config/config.local.neon` file:

```neon
parameters: 
    crm:
        addr: @environmentConfig::get('CRM_ADDR')
        api_token: @environmentConfig::get('CRM_API_TOKEN')

services:
	# add your implementation
	- Remp\MailerModule\User\Crm(%crm.addr%, %crm.api_token%)
```

The response is then fetched as process to match expected structure:

```php
$response = $this->client->post(self::ENDPOINT_LIST, [
    'form_params' => [
        'user_ids' => Json::encode($userIds),
        'page' => $page,
    ],
]);
$result = Json::decode($response->getBody(), Json::FORCE_ARRAY);
```

#### Managing user subscriptions

Mailer keeps the information about which user is subscribed to which newsletter and provides:

* APIs to handle the changes (if you're able to call the API from your user-base)
* Commands to fetch the changes (if you're able to create an API to call from Mailer)

The changes Mailer is interested in are:

* _User registration_. Mailer automatically subscribes user to all newsletters that have `auto_subscribe`
flag enabled.

  * `/api/v1/users/user-registered`:
  
    **Request:**  
    (application/x-www-form-urlencoded)
    ```POST
    user_id=STRING
    ```
    
    **Response:**
    ```
    HTTP 200 OK
    {
        "status": "ok"
    }
    ```
    
* _Email change_. Mailer keeps subscription information also to the email address. When the user changes
his/her email, Mailer needs to update that information too.

  * `/api/v1/users/email-changed`:
  
    **Request:**  
    (application/json)
    ```json
    {
        "original_email": String, // original email of user
        "new_email": Integer, // new email of user
    }
    ```
    
    **Response:**
    ```
    HTTP 200 OK
    {
        "status": "ok"
    }
    ```
    
* _Newsletter subscribe and unsubscribe_. Mailer doesn't provide frontend interface for subscribing
and unsubscribing from newsletters - site owners tend to integrate this into their layout. Due to this
Mailer provides APIs to subscribe and unsubscribe users from the newsletters
  * `/api/v1/users/subscribe`:
  
    **Request:**  
    (application/json)
    ```json
    {
        "email": String, // email of user
        "list_id": Integer, // ID of newsletter list
        "variant_id": Integer // optional, ID of newsletter list variant 
    }
    ```
    
    **Response:**
    ```
    HTTP 200 OK
    {
        "status": "ok"
    }
    ```
    
  * `/api/v1/users/un-subscribe`
  
    **Request:**  
    (application/json)
    ```json
    {
        "email": String, // email of user
        "list_id": Integer, // ID of newsletter list
    }
    ```
    
    **Response:**
    ```
    HTTP 200 OK
    {
        "status": "ok"
    }
    ```
    
In case you're not able to call these APIs, you can create console command and synchronize the data
against your APIs with your update logic.

### Mailers

By default application includes implementation of:

- [SmtpMailer](./app/models/Mailers/SmtpMailer.php)
- [MailgunMailer](./app/models/Mailers/SmtpMailer.php)

You can select the default mailer on the settings page: http://mailer.remp.press/settings/

#### Mailer integration

You can add your own implementation of Mailer to the service of your choice.

The implementation is required to extend [`Remp\MailerModule\Mailers\Mailer`](app/models/Users/Mailer.php)
abstract class.

* `protected $alias = String`: Class attribute for identification of implementation,
used only for logging purposes
* `protected $options = [ String, String, ... ]`: Class attribution for definition
of options, that should be configurable via Mailer settings page
* `supportsBatch(): bool`: Returns flag whether the implementation supports batch sending or each
email should be sent individually
* `transformTemplateParams($params)`: Mailer supports variable injection into the mail template by using `{{ variable }}`
in the template. Some emailing services require to use specific variables in email template to support batch sending.
Values for these variables are then usually provided in send API request and 3rd party service injects them right
before sending.

    That means, that the injection cannot be done by Mailer, but has to be passed onto the 3rd party service. 

    This method should replace such variables in mail template so that 3rd party is able to replace them correctly.
  * `$params`: String-based key-value pairs with values for single email.

  Two arrays are expected as return values:
  * Transformed parameters with generic template variables for 3rd party to replace.
  * Key-value pairs (possibly altered) that will be sent to the 3rd party service as values to inject
  
  Example transformation for Mailgun receives `$params` on input:
  ```php
  [
    "autologin_token": "foo",
    "mail_sender_id": "baz",
  ]
  ```
  
  And returns two arrays on output:
  * Transformed params
  ```php
  [
    "autologin_token": "%recipient.autologin_token%",
    "mail_sender_id": "%recipient.mail_sender_id",
  ]
  ```
  * Key-value pairs
  ```php
  [
    "autologin_token": "foo",
    "mail_sender_id": "baz",
  ]
  ```
  
* `send(Message $message)`: Actual implementation of sending an email. The `$message` object provides with
everything necessary to send an email:
  * `$message->getFrom()`: Key-value (email-name) pairs with senders
  * `$message->getHeader('To')`: Key-value (email-name) pairs with recipients
  * `$message->getSubject()`: Email subject
  * `$message->getBody()`: Text body
  * `$message->getHtmlBody()`: HTML body
  * `$message->getAttachments()`: Available attachments
  * `$message->getHeader('X-Mailer-Tag')`: Mail template code (slug identifier for specific email)
  * `$message->getHeader('X-Mailer-Template-Params')`: Values for template variables to be injected by 3rd party 
  * `$message->getHeader('X-Mailer-Variables')`: E-mail related metadata to be used in the implementation
  
#### Event-receiving webhooks

If you're able to configure your 3rd party service to send stats about emails via webhooks, you can create
an API handler to receive the stats and process them.

Our Mailgun webhook implementation validates the request and marks the event to be processed later asynchronously.

* API handler: [`Remp\MailerModule\Api\v2\Handlers\Mailers\MailgunEventsHandler`](app/api/v2/Handlers/Mailers/MailgunEventsHandler.php):
Mind the event type in `HermesMessage` constructor. It has to be the same as you'll use in `config.local.neon` below.
* Background event processing: [Remp\MailerModule\Hermes\MailgunEventHandler](app/hermes/MailgunEventHandler.php)

To add your own API handler and background event processing, create your implementations and register them in
`config.local.neon` file:

```neon
services:
	# ...
	apiDecider:
		setup:
			- addApiHandler(\Tomaj\NetteApi\EndpointIdentifier('POST', 1, 'mailers', 'custom-mailer'), \Remp\MailerModule\Api\v2\Handlers\Mailers\CustomMailerHandler(), \Tomaj\NetteApi\Authorization\NoAuthorization())
	hermesWorker:
		setup:
			- add('custom-mailer-event', Remp\MailerModule\Hermes\MailgunEventHandler())
```

#### Event-fetching commands 

If you are able to fetch event statistics from 3rd party service via API, we recommend writing a console command
which can be run as daemon fetching the stats as they're generated.

In our experience we find webhooks to be faster and more accurate, however they might cause a higher load on your
servers at the time of sending the newsletter.

Our Mailgun events API implementation runs as daemon end fetches the new data every 15 seconds.

* Daemon: [Remp\MailerModule\CommandsMailgunEventsCommand](app/commands/MailgunEventsCommand.php)

Your implementation then needs to be added also to the `config.local.neon` file:

```neon
services:
	# ...
	console:
		setup:
			- register(Remp\MailerModule\Commands\MailgunEventsCommand())
```

Once it's ready, you can execute it by calling `php bin/command.php mailgun:events`. The name of the command
(`mailgun:events`) is defined within your implementation, you can use any namespace and name you want. 

### Workers

For application to function properly, you need to run several backend workers handling email-sending related tasks.

To list all available console commands, run `php bin/command.php`.

We recommend to use Systemd or Supervisord for handling them. Following are Systemd configurations.

#### Background event processing (Hermes worker)

This configures handler of all asynchronous events generated by application.

Create systemd service definition in `/etc/systemd/system/remp-mailer-hermes-worker.service`. Alter the
`ExecStart` line to reflect path to your installation.

```
# BEGIN remp-mailer-hermes-worker
[Unit]
Description="REMP Mailer Hermes worker"
After=network.target

[Service]
Type=simple
UMask=0022
LimitNOFILE=1024
ExecStart=/usr/bin/sudo -u remp php /home/remp/workspace/remp/Mailer/bin/command.php worker:hermes

Restart=on-failure
RestartSec=5

[Install]
WantedBy=multi-user.target
# END remp-mailer-hermes-worker
```

Now enable and start the service:

```
sudo systemctl enable remp-mailer-hermes-worker
sudo systemctl start remp-mailer-hermes-worker
```

#### Mail sending (Mail worker)

This configures handler responsible for actual sending of emails via configured mailer.

Create systemd service definition in `/etc/systemd/system/remp-mailer-mail-worker.service`. Alter the
`ExecStart` line to reflect path to your installation.

```
# BEGIN remp-mailer-mail-worker
[Unit]
Description="REMP Mailer Mail worker"
After=network.target

[Service]
Type=simple
UMask=0022
LimitNOFILE=1024
ExecStart=/usr/bin/sudo -u remp php /home/remp/workspace/remp/Mailer/bin/command.php worker:mail

Restart=on-failure
RestartSec=5

[Install]
WantedBy=multi-user.target
# END remp-mailer-mail-worker
```

Now enable and start the service:

```
sudo systemctl enable remp-mailer-mail-worker
sudo systemctl start remp-mailer-mail-worker
```

### Scheduled events

#### Mail job preprocessing

Once you trigger the mail job to be sent, there needs to be some preprocessing to be done before the emails are sent.

Mailer acquires list of user IDs belonging to the target segment and their email addresses. It also removes all the
users that might not get the email (they might be unsubscribed) and in case of an AB testing assigns specific
mail templates to specific emails so the sending worker doesn't need to do any heavy-lifting.

Add following block to your crontab to execute the processing (alter the path based on your installation):

```
* * * * * php /home/remp/workspace/remp/Mailer/bin/command.php mail:process-job
```

#### Mail stats processing

If the default mailer supports statistics (e.g. Mailgun) and the stats are being received, you can enable stats
aggregation so they're displayed right in the job detail.

```
* * * * * php /home/remp/workspace/remp/Mailer/bin/command.php mail:job-stats
```

### Authentication

The default implementation authenticates via REMP SSO. However it is possible for Mailer
to use external authentication without the need of having SSO installed.

To replace REMP SSO with your custom authentication, you need to:

* Implement `\Nette\Security\IAuthenticator` interface

    * `authenticate(array $credentials): \Nette\Security\Identity`: Method receiving credentials, validating them against whatever source
    of truth you want to use (e.g. your own API) and returning instance of `\Nette\Security\Identity`.

        * `$credentials`: Array with credentials, where `$credentials[0]` is username and `$credentials[1]`
        is password.

* Implement `\Remp\MailerModule\Auth\BearerTokenRepositoryInterface` interface

    * `validToken(string $token): boolean`: Method receiving API token (read from `Authorization` header)
    returning whether it's valid or not based on your implementation.
    
    * `ipRestrictions(): string`: Method returning any IP addresses that should be whitelisted for given
    token. If there are no restrictions, return `*`.

Last step is to add these new implementations to `config.local.neon`. See following section to read an
example based on integration with REMP CRM and replace the classes with your own implementation.

#### REMP CRM integration

See the `Remp\MailerModule\Auth\Authenticator` implementation and
`Remp\MailerModule\Auth\RemoteBearerTokenRepository` implementation to check how you can initialize
your class the dependencies, structure the request and process the result

The following snippet needs to be added to your `config.local.neon` to enable integration with CRM.
Classes from the snippet are using REMP CRM to authenticate users and API keys.

```neon
services:
    # user authentication
    authenticator:
        class: Remp\MailerModule\Auth\Authenticator
    security.userStorage:
        class: Nette\Http\UserStorage
      
    # api authentication
    apiTokenRepository:
        class: Remp\MailerModule\Auth\RemoteBearerTokenRepository(%crm.addr%)
```

You can see that you override here two services with your own implementation. The third service
uses default Nette implementation and overrides custom REMP SSO implementation defined in `config.neon`.

From now on the authentication is not done by redirecting user to SSO but by using default sign in
screen avaiable at http://mailer.remp.press/sign/in.