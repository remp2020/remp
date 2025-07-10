# Mailer Module

Mailer Module is the core extensible package of the REMP Mailer, which serves as a tool for configuration of mailers, creation of email layouts and
templates, and configuring and sending mail jobs to selected segments of users.

## Installation

Use composer to install the package:

```bash
composer require remp/mailer-module
```

In `Bootstrap.php` file (where Nette Configurator is initialized), add module's `config.root.neon` file as the first configuration file:

```php
$configurator = new Nette\Configurator;
$configurator->addConfig(__DIR__ . '/../vendor/remp/mailer-module/src/config/config.root.neon');
// ... rest of the configuration
```

## Configuration

### Redis

You can configure default Redis keys prefix, which is used if implementation using `RedisClientTrait` enables prefixing via `useRedisKeysPrefix()` method.

```neon
mailer:
    redis_client_factory:
        prefix: foo_
```

You can then turn on prefixing for specific service (using `RedisClientTrait`) by calling `useRedisKeysPrefix()` method in configuration.

```neon
services:
    mailCache:
        setup:
            - useRedisKeysPrefix()
```

### Permission management

You can setup and control privileges for different roles by calling these setup method calls in your configuration file:

```neon
services:
    permissionManager:
        setup:
            # creates role `manager` with access to batch (resource) privileges `start` and `stop`
            - allow('manager', 'batch', ['start', 'stop'])
            # assigns `manager` role to user with email `manager@example.com`
            - assignRole('manager@example.com', 'manager')
```

**If you don't register privilege to any role, it's allowed for everyone by default because of backward compatibility.**

List of allowable privileges:

| resource | privilege | description                              |
|----------|-----------|------------------------------------------|
| batch    | start     | Allows user to start sending mail batch. |
| batch    | stop      | Allows user to stop sending mail batch.  |
| batch    | process   | Allows user to process mail batch.       |

### Email's localization

You can add support for another languages by adding array of secondary locales in the configuration of localization service:

```neon
services:
    localizationConfig:
        setup:
            - addSecondaryLocales(['cs'])
```

Default locale is setted by using `.env` variable `LOCALE`.

### Email allow list

Mailer supports allow listing email addresses to prevent sending emails to unwanted email addresses. This feature is not active by default.
To activate allow list you have to specify one or more allowed email addresses in application configuration:

```neon
services:
    emailAllowList:
        setup:
            - allow("example1@example.com")
            - allow("example2@example.com")
```

To allow all email addresses from specified domain you have to use double @ because Nette handles single @ as reference
to other service.

```neon
services:
    emailAllowList:
        setup:
            - allow("@@example.com")
            - allow("@@example2.com")
```

### Allowed domain manager

Mailer adds RTM parameters to links found in outgoing emails. To control to which links RTM parameters are added you have
to specify allowed domains. RTM parameters are only added to links that match all domain levels with one of the allowed domains. 

```neon
services:
    allowedDomainManager:
        setup:
            - addDomain('dennikn.sk')
```

### Email template service parameters

Mailer allows adding service parameters to mail templates, that are available to use in every email. To bind your own parameters, you have to register `ServiceParamsProviderInterface` implementation in which you can add your own parameters.

Registration example:

```neon
services: 
	serviceParamsProvider:
	    factory: App\MyServiceParamsProvider
```

Mailer module is by default adding two parameters (`settings` and `unsubscribe`) in `DefaultServiceParamsProvider`. If u register your own service params provider, default one will not be used. You can extend `DefaultServiceParamsProvider` instead of implementing `ServiceParamsProviderInterface` to preserve binding default service parameters.

## Technical feature description

### Mailers setup

You can setup multiple mailers to send emails with. Use `code` in `addMailer` function (see example) to differentiate between
mailers of the same type. In the settings you have to provide necessary values for each mailer.

* example setup of multiple mailers

    ```neon
    mailFactory:
        setup:
            - addMailer(Remp\MailerModule\Models\Mailer\MailgunMailer(code: us))
            - addMailer(Remp\MailerModule\Models\Mailer\MailgunMailer(code: eu))
    ```


### Newsletter lists

Lists represent categories of emails (newsletters). Their primary (and only) use case is to group single emails within
a newsletter and manage subscriptions of users to these newsletters.

When you create a new newsletter, you can specify:

* *Name.* User-friendly name of the newsletter (e.g. "Weekly newsletter", "Breaking news", ...)
* *Code.* Computer-friendly name of the newsletter (slug; e.g. "weekly_newsletter", "breaking_news", ...)
* *Auto subscribe flag.* Flag indicating whether new users (reported by external CRM via API) should be automatically
  subscribed to the newsletter or not.
* *Public flag.* Flag indicating whether the newsletter should be listed in the public listing available for end users.
  Mailer doesn't provide a user-facing frontend, so this flag is targeted primarily for the party implementing the frontend.

Any handling of subscription/unsubscription of end users from newsletters is handled via API. Publishers mostly want
to have this included within the customer zone of their systems - designed consistently with the rest of their system.
Therefore, Mailer doesn't provide **any** user-facing frontend.

To see the APIs to integration subscription management, see [Managing user subscription](#managing-user-subscriptions)
section.

### Emails and Layouts

Emails and layouts provide a way how to manually create emails with the possibility to see realtime preview of the email
while doing so. As the names suggest:

* *Layouts.* They are common and reusable parts of the emails. Usually header (containing logo and welcome message) and
  footer (containing your credentials, link to unsubscribe).

  To have emails generated correctly, place `{{ content|raw }}` to the place where content of actual *email* should be
  injected.

* *Emails.* They represent actual content of the *email* (e.g. single edition of the weekly newsletter). Every *email* has
  a couple of settings you can configure:

    * *Name.* User-friendly name of the email. It's displayed only in the administration parts of the system.
    * *Code.* Computer-friendly name of the email (slug). Primarily being used when referencing single email that's being
      sent manually.
    * *Description.* Internal description, so you know even after a year what the purpose of email was.
    * *Layout.* Layout to be used.
    * *Newsletter list.* Newsletter (category) to which this email belongs. Before the email is sent to specific end-user,
      Mailer checks whether the user is subscribed to this newsletter or not. If he/she is not, the email will not be sent.
    * *From.* Who should be used as a sender of email (e.g. `Support <support@example.com`).
    * *Subject.* Email subject.
    * *Text version.* Text version used as a fallback by email clients.
    * *HTML version.* HTML (primary) version of email that people will see. HTML version will show preview in the
      form for creation of new email.

HTML version can be edited by CodeMirror editor (html, default) or Trumbowyg editor (WYSIWYG).
Default editor can be changed using `.env` variable `TEMPLATE_EDITOR`. (options: `codemirror`, `wysiwyg`)

Text and HTML versions of *email* support [Twig syntax](https://twig.symfony.com/doc/2.x/templates.html) and you can use
standard Twig features in your templates. Mailer is able to provide custom variables to your templates. These can
originate from different sources:

* System variables.
    * `autologin`: generates and prints unique token for each email address, that can be later validated via
      [users/check-token](#get-apiv1userscheck-token) API.

      It's meant to be used within URLs (e.g. `http://dennikn.sk/email-settings{{ autologin }}`)

* Variables provided by [Generators](#generators), which can **only** be used in generator templates.
  If your generator provides `foo` variable, you can use it as `{{ foo }}` in your generator template.

* Variables provided by `IUser` (see [User integration](#user-integration)). For example if the response from your API
  includes `first_name` key as described in the user integration example, you can use it in your email template as
  `{{ first_name }}` variable.

*Note: Mailer doesn't verify presence of the variable nor does it currently provide fallback value. If you use the
custom variable and it won't be present in the `IUser` response, empty string will be injected into your email body.*

Saving the *email* doesn't trigger any sending. It creates an instance of *email* that might be sent manually (or by 3rd
parties) via API or as a *batch* within a Mailer's *job*.

### Jobs

*Jobs* can be understood as a newsletter sending orders. They can consist of smaller *batches* which provide their own
statistics. This is useful when you want to run an A/B test on smaller *batch*, evaluate and send the *email* to rest of the users.

When creating a *job*, you implicitly create also its first *batch*. The *job* has only one option shared across all batches:

* *Segment.* Defines which [*segment* of users](#segment-integration) (needs integration) should receive the email. This does not relate to
  the *newsletter* subscribers in any way. *Segment* of users should simply state the set of users you're targeting. For
  example *"people registered yesterday"* or *"people without a payment"*. It's configured on a *job* level and it's shared
  across all *batches*.

All the other options are related to the *batch* that is created with the *job*. Afterwards you'll be able to create more
*batches* within the job if necessary.

* *Method.* Specifies whether the list of emails provided by *segment* should be randomized or the emails should be sent within
  the same order as they were returned within *segment*.
* *Email A.* Primary *email* to be sent (implicitly indicates *newsletter list* which will be used for checking whether user can receive the *email*).
* *Email B.* If you want to include A/B test within your batch, you can specify the other variant of *email*.
  Distribution of variants will be uniform between all variants.
* *Number of emails.* Limits number of emails to be sent within the batch.
* *Start date.* Specifies when the batch should be sent (now or in the future).
* *Context.* Specifies identifier related to the job. Used to prevent duplicate sending of emails.

When the *job*/*batch* is created, you need to either push *"Start sending"* or *"Prepare queue" button*.
After pressing both buttons, the background processor will receive necessary information about target group of users and prepare metadata for sending daemon.

Once the metadata is ready, when pressed *"Start sending"* button, the batch will be picked up by sending daemon and
actual emails will be sent via preconfigured Mailer (SMTP, Mailgun, ...). When "Prepare queue" button was pressed, the *batch*
is in the *processed* state, all the metadata is prepared and the *number of emails* that will be actually sent is available.

There are two mechanisms that prevent duplicate sending of newsletters:

- Jobs will never send the same email (mail template) to the same email address.
- If the job has configured context, it will not send an email to addresses which already received email with the same context (even if it was different email from different job).

You can create and execute *jobs*/*batches* programmatically by using provided API endpoints.

To remove old *batches* that are in the *processed* state use `mail:remove-old-batches` command. Batch is considered to be old
when it is in the `processed` state for more than 24 hours. All the metadata prepared when changing state to `processed` can be outdated, and it is not recommended to use for sending emails.

### Generators

Generators are single-purpose implementations that help to generate HTML/text content of *emails* programmatically
based on the provided input. That means that instead of manually preparing *email* content (HTML to send) every time
you want to send an email, you can simplify the creation by implementing custom generator that can do the hard work
for you.

For example the generator can require list of URLs to your articles. When it gets them, it will parse the content of
the URLs, extracts title, excerpt and image of the article and injects that into the prepared generator template.
Person preparing the email has a guarantee that he/she won't create invalid HTML (due to typo) and the whole process
is sped up as the only thing he/she needs to enter are article URLs. The flow we just described matches with how
[`ArticleUrlParserGenerator`](./src/Models/Generators/ArticleUrlParserGenerator.php) works.

Each prepared *generator template* is directly linked to a generator implementation. It's therefore guaranteed that the
variables used within generator template will always be provided (unless the implementation contains a bug).

#### Implementing generator

To create a new generator, you need to implement [`Remp\MailerModule\Generators\IGenerator` interface](./src/Models/Generators/IGenerator.php).
Methods are described below with references to `UrlParserGenerator`:

* `generateForm(Form $form)`. Generators need a way how to get arbitrary input from user. This method should add new
  form elements into the `$form` instance and state the validation rules.

    ```php
    class UrlParserGenerator implements IGenerator
    {
        // ...
        public function generateForm(Form $form)
        {
            // ...

            $form->addTextArea('articles', 'Article')
                ->setAttribute('rows', 7)
                ->setOption('description', 'Paste article URLs. Each on separate line.')
                ->getControlPrototype()
                ->setAttribute('class', 'form-control html-editor');

            // ...
        }
    ```

* `onSubmit(callable $onSubmit)`. TODO: hide to abstract class?

* `process($values)`. Processes input values provided either by API or by generator form and generates output containing
  array with `htmlContent` and `textContent` attributes. Values of these attributes should be used as HTML/text content
  of *email*. Processing might include text replacing, fetching data from 3rd party services and anything
  that helps to shape the final HTML of *email*.

    ```php
    class UrlParserGenerator implements IGenerator
    {
        // ...
        public function process($values)
        {
            $sourceTemplate = $this->sourceTemplatesRepository->find($values->source_template_id);

            $items = [];
            $urls = explode("\n", trim($values->articles));
            foreach ($urls as $url) {
                $url = trim($url);
                $meta = $this->content->fetchUrlMeta($url);
                if ($meta) {
                    $items[$url] = $meta;
                }
            }

            $params = [
                'intro' => $values->intro,
                'footer' => $values->footer,
                'items' => $items,
                'rtm_campaign' => $values->rtm_campaign,
            ];

            $engine = $this->engineFactory->engine();
            return [
                'htmlContent' => $engine->render($sourceTemplate->content_html, $params),
                'textContent' => strip_tags($engine->render($sourceTemplate->content_text, $params)),
            ];
        }
    }
    ```

* `getWidgets()`. Provides an array of class names of widgets that might be rendered on the page after generator form
  submission. As generator "only" generates HTML/text content of email, you might want to attach some extra behavior or
  controls to the success page - such as email preview or button to create and start *job*/*batch*.

  As an example see return value of [NewsfilterGenerator](app/models/Generators/NewsfilterGenerator.php) and the
  implementation of [NewsfilterWidget](app/Components/GeneratorWidgets/Widgets/NewsfilterWidget/NewsfilterWidget.php)
  that previews provided HTML contents of email and renders extra form to provide data required to create *email*
  and *job*/*batch*.

    ```php
    class NewsfilterGenerator implements IGenerator
    {
        // ...
        public function getWidgets()
        {
            return [NewsfilterWidget::class];
        }
    }
    ```

* `apiParams()`. Provides an array of input parameters that generator requires when used via API. These parameters should
  mirror fields added in `generateForm()` method. They are utilized when calling [Generate mail API]().

    ```php
    class UrlParserGenerator implements IGenerator
    {
        // ...
        public function apiParams()
        {
            return [
                new InputParam(InputParam::TYPE_POST, 'source_template_id', InputParam::REQUIRED),
                new InputParam(InputParam::TYPE_POST, 'articles', InputParam::REQUIRED),
                new InputParam(InputParam::TYPE_POST, 'footer', InputParam::REQUIRED),
                new InputParam(InputParam::TYPE_POST, 'rtm_campaign', InputParam::REQUIRED),
                new InputParam(InputParam::TYPE_POST, 'intro', InputParam::REQUIRED)
            ];
        }
    }
    ```

* `preprocessParameters()`. Receives data provided by integrating 3rd party (e.g. Wordpress post data) and maps them
  to the parameters stated in `apiParams()`. This is a very specific use of an integration, that can be used as follows:

    * Your CMS (e.g. Wordpress) contain an integration stating that specific category of posts can utilize Mailer's generator.
      This integration directly states which generator template can be used.
    * CMS calls Mailer's *preprocess* API with Wordpress post in POST data. Generator maps Wordpress post data to the
      fields it requires as an input. That means no hard ties are made on Wordpress side.
    * As the generator implementation can be very specific (for example couple of our generators expect text version
      of Wordpress post as one of the inputs), it's OK to tie API part of the generator to the caller.
    * CMS receives back data extracted from WP post in a form, that can be submitted to the Mailer's Generator form and
      a URL where these data can be submitted.
    * CMS provides a link, that creates a hidden form, populates it with *preprocessed* data and submits it to the Mailer.

  The result is, that instead of someone manually copy-pasting data out of Wordpress to Mailer, one can simply "trigger"
  the email generation and be redirected to the Mailer's generator success page. See
  [NewsfilterGenerator](app/models/Generators/NewsfilterGenerator.php) for reference implementation.

    ```php
    class NewsfilterGenerator implements IGenerator
    {
        // ...
        public function preprocessParameters($data)
        {
            $output = new \stdClass();

            if (!isset($data->post_authors[0]->display_name)) {
                throw new PreprocessException("WP json object does not contain required attribute 'post_authors.0.display_name'");
            }
            $output->editor = $data->post_authors[0]->display_name;
            $output->from = "Denník N <info@dennikn.sk>";
            foreach ($data->post_authors as $author) {
                if ($author->user_email === "editori@dennikn.sk") {
                    continue;
                }
                $output->editor = $author->display_name;
                $output->from = $author->display_name . ' <' . $author->user_email . '>';
                break;
            }

            if (!isset($data->post_title)) {
                throw new PreprocessException("WP json object does not contain required attribute 'post_title'");
            }
            $output->title = $data->post_title;

            if (!isset($data->post_url)) {
                throw new PreprocessException("WP json object  does not contain required attribute 'post_url'");
            }
            $output->url = $data->post_url;

            if (!isset($data->post_excerpt)) {
                throw new PreprocessException("WP json object does not contain required attribute 'post_excerpt'");
            }
            $output->summary = $data->post_excerpt;

            if (!isset($data->post_content)) {
                throw new PreprocessException("WP json object does not contain required attribute 'post_content'");
            }
            $output->newsfilter_html = $data->post_content;

            return $output;
        }
    }
    ```

#### Registering generator

When your implementation is ready, register your generator in `config.local.neon`. The parameters of `registerGenerator`
method are:

* *type*: URL-friendly name of the generator which is used to link generator template with the actual implementing class.
  Removing *type* which is still used in generator templates might cause system inconsistency and errors.
* *label*: Name of the generator as is displayed in Mailer admin forms.
* *instance of `Remp\MailerModule\Generators\IGenerator`*: Implementation class used when generator is selected. It's
  safe to swap implementation instances anytime as *type* is used for referencing/linking generator templates and
  generator implementations.

```neon
services:
	# ...
	generator:
		setup:
			- registerGenerator('newsfilter', 'Newsfilter', Remp\MailerModule\Generators\NewsfilterGenerator())
```

### Base flow of actions

Here you can see simplified view of how Mailer works at following diagram.

![Mailer Sequence Overview](./resources/docs/sequence_overview.svg)

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

The implementation is required to implement [`Remp\MailerModule\Models\Segment\ISegment`](./src/Models/Segment/ISegment.php)
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

See the [`Remp\MailerModule\Models\Segment\Dummy`](./src/Models/Segment/Dummy.php) implementation as a reference
example.

##### REMP CRM implementation

See the [`Remp\MailerModule\Models\Segment\Crm`](./src/Models/Segment/Crm.php) implementation to check how you can
initialize your class the dependencies, structure the request and process the result

The constructor accept two parameters. They should come from `app/config/config.local.neon` file:

```neon
parameters:
    crm:
        addr: @environmentConfig::get('CRM_ADDR')
        api_token: @environmentConfig::get('CRM_API_TOKEN')

services:
    segmentAgreggator:
            setup:
                # add your implementation
                - register(Remp\MailerModule\Segment\Crm(%crm.addr%, %crm.api_token%))
```

#### User integration

As segments are working only with user IDs, and some of them might not be valid or active anymore, Mailer
requires an implementation that returns user information based on the ID.

The implementation is required to implement [`Remp\MailerModule\Models\Users\IUser`](./src/Models/Users/IUser.php)
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

          // you can provide optional data that can be used within your email templates, for example:
          'first_name' => String,
          'last_name' => String,
      ],
  ];
  ```

##### Dummy implementation

See the [`Remp\MailerModule\Models\Users\Dummy`](./src/Models/Users/Dummy.php) implementation as a reference example.

##### REMP CRM implementation

See the [`Remp\MailerModule\Models\Users\Crm`](./src/Models/Users/Crm.php) implementation to check how you can
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

    * [`/api/v1/users/user-registered`](#post-apiv1usersuser-registered)

* _Email change_. Mailer keeps subscription information also to the email address. When the user changes
  his/her email, Mailer needs to update that information too.

    * [`/api/v1/users/email-changed`](#post-apiv1usersemail-changed):

* _Newsletter subscribe and unsubscribe_. Mailer doesn't provide frontend interface for subscribing
  and unsubscribing from newsletters - site owners tend to integrate this into their layout. Due to this
  Mailer provides APIs to subscribe and unsubscribe users from the newsletters.

    * [`/api/v1/users/subscribe`](#post-apiv1userssubscribe)
    * [`/api/v1/users/un-subscribe`](#post-apiv1usersun-subscribe)

In case you're not able to call these APIs, you can create console command and synchronize the data
against your APIs with your update logic.

If the subscription changes aren't triggered from CRM, it is recommended to notify CRM about changes in user's setting. You can use Hermes worker `\Remp\MailerModule\Hermes\NotifyCrmSubscribeUnsubscribeHandler` which notifies CRM about the change and refreshes user's cached data. To enable the feature, register the handler in your `config.local.neon`:

```neon
services:
	hermesWorker:
		setup:
			- add('user-subscribed', Remp\MailerModule\Hermes\NotifyCrmSubscribeUnsubscribeHandler())
			- add('user-unsubscribed', Remp\MailerModule\Hermes\NotifyCrmSubscribeUnsubscribeHandler())
			- add('user-subscribed-variant', Remp\MailerModule\Hermes\NotifyCrmSubscribeUnsubscribeHandler())
			- add('user-unsubscribed-variant', Remp\MailerModule\Hermes\NotifyCrmSubscribeUnsubscribeHandler())
```

### Mailers

By default, the application includes implementation of:

- [SmtpMailer](./src/Models/Mailer/SmtpMailer.php)
- [MailgunMailer](./src/Models/Mailer/MailgunMailer.php)

You can select the default mailer on the settings page: http://mailer.remp.press/settings/

#### Mailer integration

You can add your own implementation of Mailer to the service of your choice.

The implementation is required to extend [`Remp\MailerModule\Models\Mailer`](./src/Models/Mailer/Mailer.php)
abstract class.

* `protected $alias = String`: Class attribute for identification of implementation,
  used only for logging purposes
* `protected $options = [ String, String, ... ]`: Class attribution for definition
  of options, that should be configurable via Mailer settings page
* `supportsBatch(): bool`: Returns flag whether the implementation supports batch sending or each
  email should be sent individually
* `transformTemplateParams($params)`: Mailer supports variable injection into the mail template by using `{{ variable }}`
  in the template. Some emailing services require using specific variables in email template to support batch sending.
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

**Note:** SmtpMailer implementation automatically removes `X-Mailer-Template-Params` header before sending to prevent leaking of sensitive information.  

#### Event-receiving webhooks

If you're able to configure your 3rd party service to send stats about emails via webhooks, you can create
an API handler to receive the stats and process them.

Our Mailgun webhook implementation validates the request and marks the event to be processed later asynchronously.

In case of having **multiple** mailgun mailers, each webhook URL should have mailer code specified in query params. It is the same code as specified in your `config.local.neon`

For example, you should use webhook URL `https://mailer.remp.press/api/v2/mailers/mailgun?code=us` to process events for emails sent by Mailer defined by this configuration:

```neon
services:
    mailFactory:
        setup:
            - addMailer(Remp\MailerModule\Models\Mailer\MailgunMailer(code: us))

```

* API handler: [`Remp\MailerModule\Api\v2\Handlers\Mailers\MailgunEventsHandler`](./src/Api/v2/Handlers/Mailers/MailgunEventsHandler.php):
  Mind the event type in `HermesMessage` constructor. It has to be the same as you'll use in `config.local.neon` below.
* Background event processing: [Remp\MailerModule\Hermes\MailgunEventHandler](./src/Hermes/MailgunEventHandler.php)

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

* Daemon: [Remp\MailerModule\Commands\MailgunEventsCommand](./src/Commands/MailgunEventsCommand.php)

Your implementation then needs to be added also to the `config.local.neon` file:

```neon
services:
	# ...
	console:
		setup:
			- add(Remp\MailerModule\Commands\MailgunEventsCommand())
```

Once it's ready, you can execute it by calling `php bin/command.php mailgun:events`. The name of the command
(`mailgun:events`) is defined within your implementation, you can use any namespace and name you want. In case of multiple
Mailgun mailers you can set option `code` to set up command for specific mailer.

### Workers

For application to function properly, you need to run several backend workers handling email-sending related tasks.

To list all available console commands, run `php bin/command.php`.

We recommend to use Systemd or Supervisord for handling them. Following are Systemd configurations.

#### Background event processing (Hermes worker)

This configures handler of all asynchronous events generated by application.

Create systemd service definition in `/etc/systemd/system/remp-mailer-hermes-worker.service`. Alter the
`ExecStart` line to reflect the path to your installation.

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

This configures handler responsible for actual sending of emails via a configured mailer.

Create systemd service definition in `/etc/systemd/system/remp-mailer-mail-worker.service`. Alter the
`ExecStart` line to reflect the path to your installation.

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
aggregation, so they're displayed right in the job detail.

```
* * * * * php /home/remp/workspace/remp/Mailer/bin/command.php mail:job-stats
```

### Authentication

The default implementation authenticates via REMP SSO.

However, the easiest way to start is to use `SimpleAuthenticator` with predefined list of emails and passwords. It requires no external authentication services.

#### Simple authenticator

To install `SimpleAuthenticator`, please add the following to your `config.local.neon` (`services`  section):

```neon
services:
    authenticator:
        factory: Remp\MailerModule\Models\Auth\SimpleAuthenticator
        setup:
            - addUser('admin@admin.sk', 'admin_passphrase')
```

Call `addUser()` for every valid email-password combination you want to add.

#### Custom authentication
It is possible for Mailer to use external authentication without the need of having SSO installed.

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

### REMP CRM integration

See the `Remp\MailerModule\Models\Auth\Authenticator` implementation and
`Remp\MailerModule\Models\Auth\RemoteBearerTokenRepository` implementation to check how you can initialize
your class the dependencies, structure the request and process the result

The following snippet needs to be added to your `config.local.neon` to enable integration with CRM.
Classes from the snippet are using REMP CRM to authenticate users and API keys.

```neon
services:
    # user authentication
    authenticator:
        factory: Remp\MailerModule\Models\Auth\Authenticator
    security.userStorage:
        factory: Nette\Bridges\SecurityHttp\SessionStorage

    # api authentication
    apiTokenRepository:
        factory: Remp\MailerModule\Models\Auth\RemoteBearerTokenRepository(%crm.addr%)
```

You can see that you override here two services with your own implementation. The third service
uses default Nette implementation and overrides custom REMP SSO implementation defined in `config.neon`.

From now on the authentication is not done by redirecting user to SSO but by using default sign in
screen available at http://mailer.remp.press/sign/in.

### Error tracking

Mailer comes with extension supporting tracking errors to Sentry. You can enable the tracking by setting following snippet to your `app/config/config.local.neon`:

```neon
extensions:
    sentry: Rootpd\NetteSentry\DI\SentryExtension

sentry:
	dsn: https://0123456789abcdef0123456789abcdef@sentry.example.com/1
	environment: development
	user_fields:
		- email
```

Please be aware, that the tracking works only if you have debug mode disabled.
By default, debug mode is enabled only when `ENV` is set to `local`.

### Healthcheck

Route `http://mailer.remp.press/health` provides health check for database, Redis, storage and logging.

Returns:

- **200 OK** and JSON with list of services _(with status "OK")_.
- **500 Internal Server Error** and JSON with description of problem. E.g.:

    ```
    {
      "status":"PROBLEM",
      "log":{
        "status":"PROBLEM",
        "message":"Could not write to log file",
        "context": // error or thrown exception...
      //...
    }
    ```

## Database tables migration

Because of need of changing primary keys (`int` -> `bigint`), in tables that contain lots of data (or have risk of overflowing primary key if its `int`), we had to create migration process. Since some tables are very exposed and cannot be locked for more than a couple of seconds, we decided to create new tables, migrate the data manually and keep the old and new table in sync while migrating.

_This migration process is necessary only for installations after specific version for specific table, and is two steps process._

### Mail logs migration (version < 1.1.0)

Consists of `mail_logs` and `mail_log_conversions` table migration. Also contains adding `user_id` column to `mail_logs` table.

Steps:
1. running phinx migration `CreateNewMailLogsAndMailConversionsTable` - which creates new tables `mail_logs_v2` and `mail_log_conversions_v2` (in case there is no data in tables, migration just changes type of primary key and next steps are not needed)
2. running command `mail:migrate-mail-logs-and-conversions` which copies data from old tables to new (e.g. `mail_logs` to `mail_logs_v2`) - command will after successful migration atomically rename tables (e.g. `mail_logs` -> `mail_logs_old` and `mail_logs_v2` -> `mail_logs`) so when the migration ends only new tables are used

It's recommended to run (in order):
1. `mail:bigint_migration_cleanup mail_log_conversions`
2. `mail:bigint_migration_cleanup mail_logs`

commands, at least 2 weeks (to preserve backup data, if some issue emerges) after successful migration to drop left-over tables.

### User subscription migration (version < 1.2.0)

Consists of `mail_user_subscriptions` and `mail_user_subscription_variants` table migration.

Steps:
1. running phinx migration `CreateNewMailUserSubscriptionsAndMailUserSubscriptionVariantsTables` - which creates new tables `mail_user_subscriptions_v2` and `mail_user_subscription_variants_v2` (in case there is no data in tables, migration just changes type of primary key and next steps are not needed)
2. running command `mail:migrate-user-subscriptions-and-variants` which copies data from old tables to new (e.g. `mail_user_subscriptions` to `mail_user_subscriptions_v2`) - command will after successful migration atomically rename tables (e.g. `mail_user_subscriptions` -> `mail_user_subscriptions_old` and `mail_user_subscriptions_v2` -> `mail_user_subscriptions`) so when the migration ends only new tables are used

It's recommended to run (in order):
1. `mail:bigint_migration_cleanup mail_user_subscription_variants`
2. `mail:bigint_migration_cleanup mail_user_subscriptions`

commands, at least 2 weeks (to preserve backup data, if some issue emerges) after successful migration to drop left-over tables.

### Autologin tokens migration (version < 1.3.0)

Consists of `autologin_tokens` table migration.

Steps:
1. running phinx migration `CreateNewAutologinTokensTable` - which creates new table `autologin_tokens_v2` (in case there is no data in table, migration just changes type of primary key and next steps are not needed)
2. running command `mail:migrate-autologin-tokens` which copies data from old tables to new (`autologin_tokens` to `autologin_tokens_v2`) - command will after successful migration atomically rename tables (`autologin_tokens` -> `autologin_tokens_old` and `autologin_tokens_v2` -> `autologin_tokens`) so when the migration ends only new tables are used

It's recommended to run `mail:bigint_migration_cleanup autologin_tokens` command, at least 2 weeks (to preserve backup data, if some issue emerges) after successful migration to drop left-over tables.

## API Documentation

All examples use `http://mailer.remp.press` as a base domain. Please change the host to the one you use
before executing the examples.

All examples use `XXX` as a default value for authorization token, please replace it with the
real token API token which can be acquired in the REMP SSO.

API responses can contain following HTTP codes:

| Value           | Description                                             |
|-----------------|---------------------------------------------------------|
| 200 OK          | Successful response, default value                      |
| 400 Bad Request | Invalid request (missing required parameters)           |
| 403 Forbidden   | The authorization failed (provided token was not valid) |
| 404 Not found   | Referenced resource wasn't found                        |

If possible, the response includes `application/json` encoded payload with a message explaining
the error further.

---

#### POST `/api/v1/users/user-registered`

When user is registered, Mailer should be notified so it can start tracking newsletter subscription for this new email
address. This new email address will be automatically subscribed to any newsletter that has enabled *auto subscribe*
option.

##### *Headers:*

| Name          | Value           | Required | Description |
|---------------|-----------------|----------|-------------|
| Authorization | Bearer *String* | yes      | API token.  |

##### *Params:*

| Name    | Value                                                 | Required | Description            |
|---------|-------------------------------------------------------|----------|------------------------|
| email   | *String*                                              | yes      | Email address of user. |
| user_id | *String/Integer* _(validated by FILTER_VALIDATE_INT)_ | yes      | ID of user.            |

##### *Example:*

```shell
curl -X POST \
  http://mailer.remp.press/api/v1/users/user-registered \
  -H 'Authorization: Bearer XXX' \
  -H 'Content-Type: application/x-www-form-urlencoded' \
  -d 'email=admin%40example.com&user_id=12345'
```

Response:

```json5
{
    "status": "ok"
}
```

---

#### POST `/api/v1/users/bulk-user-registered`

Similar to previous api `users/user-registerd`. Subscribes multiple provided users.

When user is registered, Mailer should be notified so it can start tracking newsletter subscription for this new email
address. This new email address will be automatically subscribed to any newsletter that has enabled *auto subscribe*
option.

##### *Headers:*

| Name          | Value           | Required | Description |
|---------------|-----------------|----------|-------------|
| Authorization | Bearer *String* | yes      | API token.  |

##### *Body:*

```json5
{
  "users": [
      {
        "email": "admin@example.com",
        "user_id": "12345"
      },
      {
        "email": "test@example.com",
        "user_id": "67890"
      }
  ]
}
```

###### *Properties of one user*

| Name    | Value                                                 | Required | Description            |
|---------|-------------------------------------------------------|----------|------------------------|
| email   | *String*                                              | yes      | Email address of user. |
| user_id | *String/Integer* _(validated by FILTER_VALIDATE_INT)_ | yes      | ID of user.            |

##### *Example:*

```shell
curl -X POST \
  http://mailer.remp.press/api/v1/users/bulk-user-registered \
  -H 'Authorization: Bearer XXX' \
  -H 'Content-Type: application/json' \
  -d '{
        "users": [
          {
            "email": "admin@example.com",
            "user_id": 12345
          },
          {
            "email": "test@example.com",
            "user_id": "67890"
          }
        ]
      }'
```

Response:

```json5
{
    "status": "ok"
}
```

###### *Example with errors:*

```shell
curl -X POST \
  http://mailer.remp.press/api/v1/users/bulk-user-registered \
  -H 'Authorization: Bearer XXX' \
  -H 'Content-Type: application/json' \
  -d '{
        "users": [
          {
            "email": "admin@example.com"
          },
          {
            "user_id": "67890"
          },
          {
            "email": "qa@example.com",
            "user_id": "qa123"
          }
        ]
      }'
```

Response:

```json5
{
  "status": "error",
  "message": "Input data contains errors. See included list of errors.",
  "errors": {
    "element_0": "Required field missing: user_id.",
    "element_1": "Required field missing: email.",
    "element_2": "Invalid field: 'user_id' must be integer. Got [qa123]."
  }
}
```

---

#### POST `/api/v1/users/is-unsubscribed`

API call that checks if user is unsubscribed from given newsletter list.

##### *Headers:*

| Name          | Value           | Required | Description |
|---------------|-----------------|----------|-------------|
| Authorization | Bearer *String* | yes      | API token.  |

##### *Body:*

```json5
{
  // required
  "user_id": 1, // Integer; ID of user
  "email": "test@test.sk", // String; Email of user,
  "list_id": 1 // Integer; ID of newsletter
}
```

##### *Example:*

```shell
curl -X POST \
  http://mailer.remp.press/api/v1/users/is-unsubscribed \
  -H 'Authorization: Bearer XXX' \
  -H 'Content-Type: application/json' \
  -d '{
	"user_id": 123,
    "email": "test@test.sk",
	"list_id": 1
}'
```

Response:

```json5
{
  "is_unsubscribed": true
}
```

---

#### POST `/api/v1/users/is-subscribed`

API call that checks if user is subscribed from given newsletter list (and optionally its variant).

##### *Headers:*

| Name          | Value           | Required | Description |
|---------------|-----------------|----------|-------------|
| Authorization | Bearer *String* | yes      | API token.  |

##### *Body:*

```json5
{
  // required
  "user_id": 1, // Integer; ID of user
  "email": "test@test.sk", // String; Email of user,
  "list_id": 1 // Integer; ID of newsletter
  "variant_id": 1 // Integer; ID of variant
}
```

##### *Example:*

```shell
curl -X POST \
  http://mailer.remp.press/api/v1/users/is-subscribed \
  -H 'Authorization: Bearer XXX' \
  -H 'Content-Type: application/json' \
  -d '{
	"user_id": 123,
    "email": "test@test.sk",
	"list_id": 1,
	"variant_id": 99,
	
}'
```

Response:

```json5
{
  "is_subscribed": true
}
```

---

#### POST `/api/v1/users/user-preferences`

API call to get subscribed newsletter lists and their variants.

##### *Headers:*

| Name          | Value           | Required | Description |
|---------------|-----------------|----------|-------------|
| Authorization | Bearer *String* | yes      | API token.  |

##### *Body:*

```json5
{
  //required
  "user_id": 1, // Integer; ID of user
  "email": "test@test.com", // String; Email to get preferences for

  // optional
  "subscribed": true // Boolean; Get only subscribed newsletters
}
```

##### *Example:*

```shell
curl -X POST \
  http://mailer.remp.press/api/v1/users/user-preferences \
  -H 'Authorization: Bearer XXX' \
  -H 'Content-Type: application/json' \
  -d '{
	"user_id": 123,
	"email": "test@test.com"
}'
```

Response:

```json5
[
  {
    "id": 1,
    "code": "demo-weekly-newsletter",
    "title": "DEMO Weekly newsletter",
    "is_subscribed": true,
    "variants": [],
    "updated_at": "2020-04-06T00:15:32+02:00"
  },
  {
    "id": 2,
    "code": "123",
    "title": "Test",
    "is_subscribed": true,
    "variants": [
      {
        "id": 2,
        "code": "test-variant",
        "title": "Test Variant"
      }
    ],
    "updated_at": "2020-04-10T15:27:35+02:00"
  }
]
```

---

#### POST `/api/v1/users/subscribe`

API call subscribes email address to the given newsletter. Newsletter has to be already created.
Currently, there's no API endpoint for that and the newsletter needs to be created manually.
Please visit `/list/new` to create a newsletter via web admin.

If newsletter list has variants, following behavior applies:

- For `is_multi_variant=true` newsletter **without** the default variant, all variants are subscribed when user subscribes to the newsletter.
- For `is_multi_variant=true` newsletter **with** the default variant, only the default variant is subscribed when user subscribes to the newsletter.
- For `is_multi_variant=false` newsletter **without** the default variant, no variant is subscribed automatically when user subscribes to the newsletter. Variant should be provided explicitly for this scenario.
- For `is_multi_Variant=false` newsletter **with** the default variant, the default variant is subscribed automatically unless the variant was provided explicitly.

Please be aware, that the described variant subscription behavior can be suppressed by sending `force_no_variant_subscription=true`. If sent, no variant is subscribed, even if it was provided explicitly.

##### *Headers:*

| Name          | Value           | Required | Description |
|---------------|-----------------|----------|-------------|
| Authorization | Bearer *String* | yes      | API token.  |

##### *Body:*

```json5
{
  "email": "admin@example.com", // String; email of the user
  "user_id": 123, // Integer; ID of the user

  // one of the following is required
  "list_id": 14, // Integer; ID of the newsletter list you're subscribing the user to
  "list_code": "alerts", // String; code of the newsletter list you're subscribing the user to

  // optional
  "variant_id": 123, // Integer; ID of the newsletter variant to subscribe
  "variant_code": "author.123", // String; Code of the newsletter variant to subscribe
  "send_accompanying_emails": true, // Boolean; Whether to send welcome or goodbye email to the subscribed/unsubscribed user. Defaults to TRUE.
  "force_no_variant_subscription": true, // Boolean; If list_id has variants, flag indicates whether the default behavior should be suppressed and no variant subscribed. Defaults to FALSE.

  // optional RTM parameters for tracking "what" made the user unsubscribe
  "rtm_params": { // Object; optional RTM parameters for pairing which email caused the user to unsubscribe. RTM params are generated into the email links automatically.
    "rtm_source": "newsletter_daily",
    "rtm_medium": "email",
    "rtm_campaign": "daily-newsletter-11.3.2019-personalized",
    "rtm_content": "26026"
  }
}
```

##### *Example:*

```shell
curl -X POST \
  http://mailer.remp.press/api/v1/users/subscribe \
  -H 'Authorization: Bearer XXX' \
  -H 'Content-Type: application/json' \
  -d '{
	"email": "admin@example.com",
	"user_id": 123,
	"list_id": 1,
	"variant_id": 1,
    "send_accompanying_emails": true,
    // optional RTM parameters for tracking "what" made the user subscribe
    "rtm_params": {
        "rtm_source": "newsletter_daily",
        "rtm_medium": "email",
        "rtm_campaign": "daily-newsletter-11.3.2019-personalized",
        "rtm_content": "26026"
    },
}'
```

Response:

```json5
{
    "status": "ok",
    "subscribed_variants": [ // in case mail_type has variants, list of subscribed variants is listed here
        {id: 2, code: "test", title: "test", sorting:  1},
        {id: 3, code: "test2", title: "test 2", sorting: 2}
    ]
}
```

---

#### POST `/api/v1/users/un-subscribe`

API call unsubscribes email address from the given newsletter. Optionally, one can specify newsletter variant to unsubscribe.
By default, if list type has multiple variants and user unsubscribes from all of them, system also unsubscribes from the main newsletter list. 
To change the default behaviour, one can specify `keep_list_subscription` parameter - if `true`, this endpoint call retains main list subscription 
even if no variant is subscribed.

Endpoint accepts an optional array of RTM (REMP's UTM) parameters. Every link in email send by Mailer contain RTM parameters
referencing to the specific instance of sent email. If user unsubscribes via specific email, your frontend will also
receive special RTM parameters, that can be passed to this API call. For instance link to unsubscribe from our daily
newsletter might look like this:

```
https://predplatne.dennikn.sk/mail/mail-settings/un-subscribe-email/newsletter_daily?rtm_source=newsletter_daily&rtm_medium=email&rtm_campaign=daily-newsletter-11.3.2019-personalized&rtm_content=26026
```

The `newsletter_daily` stands for *newsletter list code*. RTM parameters reference specific *email* and specific *batch*
which generated this email. If you won't provide/pass these RTM parameters, statistics related to unsubscribe rate
of emails won't be available.

##### *Headers:*

| Name          | Value           | Required | Description |
|---------------|-----------------|----------|-------------|
| Authorization | Bearer *String* | yes      | API token.  |

##### *Body:*

```json5
{
    "email": "admin@example.com", // String; email of the user
    "user_id": 123, // Integer; ID of the user

    // one of the following is required
    "list_id": 14, // Integer; ID of the newsletter list you're subscribing the user to
    "list_code": "alerts", // String; code of the newsletter list you're subscribing the user to

    // optional
    "variant_id": 1, // Integer;  ID of newsletter variant to unsubscribe
    "variant_code": "author.123", // String; Code of the newsletter variant to subscribe

    // optional, useful only when variant_id or variant_code is specified
    "keep_list_subscription": false, // Boolean; specifies if list subscription should be kept when no list variant remains subscribed   

    // optional RTM parameters for tracking "what" made the user unsubscribe
    "rtm_params": { // Object; optional RTM parameters for pairing which email caused the user to unsubscribe. RTM params are generated into the email links automatically.
        "rtm_source": "newsletter_daily",
        "rtm_medium": "email",
        "rtm_campaign": "daily-newsletter-11.3.2019-personalized",
        "rtm_content": "26026"
    }
}
```

##### *Example:*

```shell
curl -X POST \
  http://mailer.remp.press/api/v1/users/un-subscribe \
  -H 'Authorization: Bearer XXX' \
  -H 'Content-Type: application/json' \
  -d '{
	"email": "admin@example.com",
	"user_id": 12,
	"list_id": 1,
	"variant_id": 1,
	"rtm_params": {
		"rtm_source": "newsletter_daily",
		"rtm_medium": "email",
		"rtm_campaign": "daily-newsletter-11.3.2019-personalized",
		"rtm_content": "26026"
	}
}'
```

Response:

```json5
{
    "status": "ok"
}
```

---

#### POST `/api/v1/users/bulk-subscribe`

Bulk subscribe allows subscribing and unsubscribing multiple users in one batch. For details about subscribe / unsubscribe see individual calls above.

##### *Headers:*

| Name          | Value           | Required | Description |
|---------------|-----------------|----------|-------------|
| Authorization | Bearer *String* | yes      | API token.  |

##### *Body:*


```json5
{
  "users": [
    {
      "email": "admin@example.com", // String; email of the user
      "user_id": 12345, // Integer; ID of the user

      // one of the following is required
      "list_id": 14, // Integer; ID of the newsletter list you're subscribing the user to
      "list_code": "alerts", // String; code of the newsletter list you're subscribing the user to

      "variant_id": 3, // Integer; ID of the variant of newsletter list you're subscribing user to. Must belong to provided list.
      "variant_code": "author.123", // String; Code of the newsletter variant to subscribe
      "subscribe": false, // Boolean; indicates if you want to subscribe or unsubscribe user

      // optional RTM parameters used only if `subscribe:false` for tracking "what" made the user unsubscribe
      "rtm_params": { // Object; optional RTM parameters for pairing which email caused the user to unsubscribe. RTM params are generated into the email links automatically.
        "rtm_source": "newsletter_daily",
        "rtm_medium": "email",
        "rtm_campaign": "daily-newsletter-11.3.2019-personalized",
        "rtm_content": "26026"
      },

      // optional
      "send_accompanying_emails": true, // Boolean; Flag whether to send welcome or goodbye email to the user whom subscription is being changed. Defaults to TRUE.
      "force_no_variant_subscription": true // Boolean; If list_id has variants, flag indicates whether the default behavior should be suppressed and no variant subscribed. Defaults to FALSE.
    }
  //...
  ]
}
```

###### *Properties of one users element*

| Name                     | Value                                                 | Required                         | Description                                                                                                                                |
|--------------------------|-------------------------------------------------------|----------------------------------|--------------------------------------------------------------------------------------------------------------------------------------------|
| email                    | *String*                                              | yes                              | Email address of user.                                                                                                                     |
| user_id                  | *String/Integer* _(validated by FILTER_VALIDATE_INT)_ | yes                              | ID of user.                                                                                                                                |
| subscribe                | *Boolean*                                             | yes                              | Flag to indicate if user should subscribed or un-subscribed.                                                                               |
| list_id                  | *Integer*                                             | yes _(use list_id or list_code)_ | ID of mail list.                                                                                                                           |
| list_code                | *String*                                              | yes _(use list_id or list_code)_ | Code of mail list.                                                                                                                         |
| variant_id               | *Integer*                                             | no                               | Optional ID of variant.                                                                                                                    |
| rtm_params               | *Object*                                              | no                               | Optional RTM parameters for pairing which email caused the user to unsubscribe.                                                            |
| utm_params               | *Object*                                              | no                               | (Deprecated) UTM parameters are deprecated, but if no RTM paramters are found, system will try to use these.                               |
| send_accompanying_emails | *Boolean*                                             | no                               | Whether or not to send welcome or goodbye email to the user whom subscription is being changed. Defaults to **TRUE**. |


##### *Example:*

```shell
curl -X POST \
  http://mailer.remp.press/api/v1/users/bulk-subscribe \
  -H 'Authorization: Bearer XXX' \
  -H 'Content-Type: application/json' \
  -d '{
        "users": [
          {
            "email": "admin@example.com",
            "user_id": 1,
            "subscribe": true,
            "list_id": 2
          },
          {
            "email": "test@example.com",
            "user_id": 2,
            "subscribe": true,
            "list_code": "demo-weekly-newsletter",
            "variant_id": 4
          },
          {
            "email": "silent@example.com",
            "user_id": 3,
            "subscribe": false,
            "list_id": 3,
            "rtm_params": {
              "rtm_source": "newsletter_daily",
              "rtm_medium": "email",
              "rtm_campaign": "daily-newsletter-11.3.2019-personalized",
              "rtm_content": "26026"
            }
          }
        ]
}'
```

Response:

```json5
{
    "status": "ok"
}
```

###### *Example with errors:*

```shell
curl -X POST \
  http://mailer.remp.press/api/v1/users/bulk-subscribe \
  -H 'Authorization: Bearer XXX' \
  -H 'Content-Type: application/json' \
  -d '{
        "users": [
          {
            "email": "admin@example.com"
          },
          {
            "user_id": "67890"
          },
          {
            "email": "qa@example.com",
            "user_id": "qa123"
          },
          {
            "email": "qa1@example.com",
            "user_id": "123"
          },
          {
            "email": "qa2@example.com",
            "user_id": "123",
            "list_id": 1
          }
        ]
      }'
```

Error Response:

```json5
{
  "status": "error",
  "message": "Input data contains errors. See included list of errors.",
  "errors": {
    "element_0": "Required field missing: `user_id`.",
    "element_1": "Required field missing: `email`.",
    "element_2": "Parameter `user_id` must be integer. Got [qa123].",
    "element_3": "Required field missing: `list_id` or `list_code`.",
    "element_4": "Required field missing: `subscribe`.",
  }
}
```

---

#### GET `/api/v1/users/check-token`

Verifies validity of autologin token provided within email.

Each email can contain `{{ autologin }}` block within the template. When used within an URL
(such as `https://predplatne.dennikn.sk{{ autologin }}`), special token is generated and appended to the URL.

Token is appended as a query parameter `token`, for example:

```
https://predplatne.dennikn.sk/?token=206765522b71289504ae766389bf741x
```

Your frontend application can read this token on a visit and verify against this API whether it's still valid or not.
If it's valid, you can automatically log user in based on the ID/email the endpoint provides.

##### *Body:*

```json5
{
	"token": "206765522b71289504ae766389bf741x", // String; token read from query string
}
```

##### *Example:*

```shell
curl -X POST \
  http://mailer.remp.press/api/v1/users/check-token \
  -H 'Content-Type: application/json' \
  -d '{
	"token": "206765522b71289504ae766389bf741x"
}'
```

Response:

```json5
{
    "status": "ok",
    "email": "admin@example.com"
}
```

---

#### POST `/api/v1/users/email-changed`

If your system allows users to change their email addrses, Mailer needs to be notified about the address change as the
subscription information is being stored on *user_id*/*email* level.

##### *Headers:*

| Name          | Value           | Required | Description |
|---------------|-----------------|----------|-------------|
| Authorization | Bearer *String* | yes      | API token.  |

##### *Params:*

| Name           | Value    | Required | Description                     |
|----------------|----------|----------|---------------------------------|
| original_email | *String* | yes      | Original email address of user. |
| new_email      | *String* | yes      | New email address of user.      |

##### *Example:*

```shell
curl -X POST \
  http://mailer.remp.press/api/v1/users/email-changed \
  -H 'Authorization: Bearer XXX' \
  -H 'Content-Type: application/x-www-form-urlencoded' \
  -d 'original_email=admin%40example.com&new_email=user%40example.com'
```

Response:

```json5
{
    "status": "ok"
}
```

---

#### POST `/api/v1/users/logs-count-per-status`

Returns number of emails matching the status based on given timeframe. Count is returned separately for each selected status.

##### *Headers:*

| Name          | Value           | Required | Description |
|---------------|-----------------|----------|-------------|
| Authorization | Bearer *String* | yes      | API token.  |

##### *Body:*

```json5
{
  // required
  "email": "test@test.com", // String; email
  "filter": ["sent_at", "delivered_at"],

  // optional
  "from": "2020-04-07T13:33:44+02:00", // String - RFC 3339 format; Restrict results to specific from date, optional
  "to": "2020-04-10T13:33:44+02:00" // String - RFC 3339 format; Restrict results to specific to date, optional
}
```

##### *Example:*

```shell
curl -X POST \
  http://mailer.remp.press/api/v1/users/logs-count-per-status \
  -H 'Authorization: Bearer XXX' \
  -H 'Content-Type: application/json' \
  -d '{
    "email": "test@test.com", // String; email
    "filter": ["sent_at", "delivered_at"],
    "from": "2020-04-07T13:33:44+02:00", // String - RFC 3339 format; Restrict results to specific from date, optional
    "to": "2020-04-10T13:33:44+02:00" // String - RFC 3339 format; Restrict results to specific to date, optional
}'
```

Response:

```json5
{
    "sent_at": 2,
    "delivered_at": 2
}
```

---

#### POST `/api/v1/users/logs`

Returns mail logs based on given criteria.

##### *Headers:*

| Name          | Value           | Required | Description |
|---------------|-----------------|----------|-------------|
| Authorization | Bearer *String* | yes      | API token.  |

##### *Body:*

```json5
{
  // required only one of email/user_id
  "email": "test@test.com", // String; email
  "user_id": 123, // Integer

  // optional
  "filter": { // Available filters are delivered_at, clicked_at, opened_at, dropped_at, spam_complained_at, hard_bounced_at
    "hard_bounced_at": {
      "from": "2020-04-07T13:33:44+02:00", // String - RFC 3339 format; Restrict results to specific from date, optional
      "to": "2020-04-10T13:33:44+02:00" // String - RFC 3339 format; Restrict results to specific to date, optional
    }
  },
  "mail_template_ids": [1,2,3], // Array of integers; Ids of templates
  "page": 1, // Integer;
  "limit": 2 // Integer; Limit of results per page
}
```

##### *Filter can also be in a format:*

```json5
{
  "filter": ["dropped_at", "delivered_at"] // Available filters are sent_at, delivered_at, clicked_at, opened_at, dropped_at, spam_complained_at, hard_bounced_at
}
```

##### *Example:*

```shell
curl -X POST \
  http://mailer.remp.press/api/v1/users/logs \
  -H 'Authorization: Bearer XXX' \
  -H 'Content-Type: application/json' \
  -d '{
    "filter": {
        "dropped_at": {},
        "delivered_at": {
          "to": "2020-04-07T13:33:44+02:00"
        },
        "spam_complained_at": {
          "from": "2020-04-07T13:33:44+02:00"
        },
        "hard_bounced_at": {
          "from": "2020-04-07T13:33:44+02:00",
          "to": "2020-04-10T13:33:44+02:00"
        }
    },
    "email": "test@test.com",
    "user_id": 123,
    "mail_template_ids": [1,2,3],
    "page": 1,
    "limit": 2
}'
```

Response:

```json5
[
 {
    "id": 2,
    "email": "test@test.com",
    "subject": null,
    "mail_template": {
      "id": 1,
      "code": "demo_email",
      "name": "Demo email"
    },
    "sent_at": "2020-04-08T19:26:00+02:00",
    "delivered_at": "2020-04-08T13:33:44+02:00",
    "dropped_at": "2020-04-08T19:28:36+02:00",
    "spam_complained_at": null,
    "hard_bounced_at": null,
    "clicked_at": null,
    "opened_at": null,
    "attachment_size": null
  },
  {
    "id": 4,
    "email": "test@test.com",
    "subject": null,
    "mail_template": {
      "id": 2,
      "code": "example_email",
      "name": "Example email"
    },
    "sent_at": "2020-04-08T19:26:00+02:00",
    "delivered_at": null,
    "dropped_at": "2020-04-08T19:28:46+02:00",
    "spam_complained_at": null,
    "hard_bounced_at": null,
    "clicked_at": null,
    "opened_at": null,
    "attachment_size": null
  }
]
```

---

#### POST `/api/v1/users/delete`

Removes all user data for given email

##### *Headers:*

| Name          | Value           | Required | Description |
|---------------|-----------------|----------|-------------|
| Authorization | Bearer *String* | yes      | API token.  |

##### *Body:*

```json5
{
  "email": "example@example.com" // String; email
}
```

##### *Example:*

```shell
curl -X POST \
  http://mailer.remp.press/api/v1/users/delete \
  -H 'Authorization: Bearer XXX' \
  -H 'Content-Type: application/json' \
  -d '{
    "email": "example@example.com"
}'
```

Response:

- If user data were removed, response with HTTP code **204 No Content** will be returned.
- If no logs were found, response with HTTP code **404 Not found** is returned:


```json5
{
  "status":"error",
  "code":"user_not_found",
  "message":"No user data found for email [example@example.com]."
}
```

---


#### GET `/api/v1/mailers/mail-types`

Lists all available *newsletter lists* (mail types). *Code* of the newsletter is required when creating
new *email* template via API.

##### *Headers:*

| Name          | Value           | Required | Description |
|---------------|-----------------|----------|-------------|
| Authorization | Bearer *String* | yes      | API token.  |

##### *Params:*

| Name           | Value     | Required | Description                                                                                                            |
|----------------|-----------|----------|------------------------------------------------------------------------------------------------------------------------|
| code           | *String*  | no       | Filter only newsletter (mail type) with specific code.                                                                 |
| public_listing | *Boolean* | no       | Flag whether only newsletters (mail types) hat should/shouldn't be available to be listed publicly should be returned. |

##### *Example:*

```shell
curl -X GET \
  http://mailer.remp.press/api/v1/mailers/mail-types?public_listing=1&code=demo-weekly-newsletter \
  -H 'Authorization: Bearer XXX'
```

Response:

```json5
{
  "status": "ok",
  "data": [
    {
      "id": 1,
      "code": "demo-weekly-newsletter",
      "image_url": null,
      "preview_url": null,
      "page_url": null,
      "title": "DEMO Weekly newsletter",
      "description": "Example mail list",
      "locked": false,
      "is_multi_variant": true,
      "variants": {
        "2": "test",
        "3": "test2"
      }
    }
  ]
}
```

---

#### GET `/api/v2/mailers/mail-types`

Lists all available *newsletter lists* (mail types). *Code* of the newsletter is required when creating
new *email* template via API.

##### *Headers:*

| Name          | Value           | Required | Description |
|---------------|-----------------|----------|-------------|
| Authorization | Bearer *String* | yes      | API token.  |

##### *Params:*

| Name                    | Value      | Required | Description                                                                                                            |
|-------------------------|------------|----------|------------------------------------------------------------------------------------------------------------------------|
| code                    | *String[]* | no       | Filter only newsletters (mail types) with specific code. Multiple codes can be requested.                              |
| mail_type_category_code | *String*   | no       | Filter only newsletters (mail types) of specific category. Multiple categories can be requested.                       |
| public_listing          | *Boolean*  | no       | Flag whether only newsletters (mail types) hat should/shouldn't be available to be listed publicly should be returned. |

##### *Example:*

```shell
curl -X GET \
  http://mailer.remp.press/api/v1/mailers/mail-types?public_listing=1&code[]=demo-weekly-newsletter \
  -H 'Authorization: Bearer XXX'
```

Response:

```json5
{
  "status": "ok",
  "data": [
    {
      "id": 1,
      "code": "demo-weekly-newsletter",
      "image_url": null,
      "preview_url": null,
      "page_url": null,
      "title": "DEMO Weekly newsletter",
      "description": "Example mail list",
      "locked": false,
      "is_multi_variant": true,
      "variants": {
        "2": "test",
        "3": "test2"
      }
    }
  ]
}
```

---

#### GET `/api/v3/mailers/mail-types`

Lists all available *newsletter lists* (mail types). *Code* of the newsletter is required when creating
new *email* template via API. Compared to v2, v3 returns all important variant attributes (`id`, `code`, `sorting`, `title`).

##### *Headers:*

| Name          | Value           | Required | Description |
|---------------|-----------------|----------|-------------|
| Authorization | Bearer *String* | yes      | API token.  |

##### *Params:*

| Name                    | Value      | Required | Description                                                                                                            |
|-------------------------|------------|----------|------------------------------------------------------------------------------------------------------------------------|
| code                    | *String[]* | no       | Filter only newsletters (mail types) with specific code. Multiple codes can be requested.                              |
| mail_type_category_code | *String*   | no       | Filter only newsletters (mail types) of specific category. Multiple categories can be requested.                       |
| public_listing          | *Boolean*  | no       | Flag whether only newsletters (mail types) hat should/shouldn't be available to be listed publicly should be returned. |

##### *Example:*

```shell
curl -X GET \
  http://mailer.remp.press/api/v1/mailers/mail-types?public_listing=1&code[]=demo-weekly-newsletter \
  -H 'Authorization: Bearer XXX'
```

Response:

```json5
{
  "status": "ok",
  "data": [
    {
      "id": 1,
      "code": "demo-weekly-newsletter",
      "image_url": null,
      "preview_url": null,
      "page_url": null,
      "title": "DEMO Weekly newsletter",
      "description": "Example mail list",
      "locked": false,
      "is_multi_variant": true,
      "variants": {
        "2": {id: 2, code: "test", title: "test", sorting:  1},
        "3": {id: 3, code: "test2", title: "test 2", sorting: 2}
      }
    }
  ]
}
```

---

#### GET `/api/v1/mailers/mail-type-categories`

Get available categories of newsletters.

##### *Headers:*

| Name          | Value           | Required | Description |
|---------------|-----------------|----------|-------------|
| Authorization | Bearer *String* | yes      | API token.  |

##### *Example:*

```shell
curl -X GET \
  http://mailer.remp.press/api/v1/mailers/mail-type-categories \
  -H 'Authorization: Bearer XXX'
```

Response:

```json5
[
  {
    "id": 1,
    "title": "Newsletters",
    "sorting": 100,
    "show_title": true,
    "code": "newsletters"
  },
  {
    "id": 2,
    "title": "System",
    "sorting": 999,
    "show_title": false,
    "code": "system"
  }
]
```

---

#### POST `/api/v1/mailers/mail-type-upsert`

Creates or updates mail type (newsletter list). Endpoint complements creation of newsletter list via web interface.

If existing `id`/`code` is provided, API handler updates existing record, otherwise a record is created.
Field `id` has higher precedence in finding the existing record.

##### *Headers:*

| Name          | Value           | Required | Description |
|---------------|-----------------|----------|-------------|
| Authorization | Bearer *String* | yes      | API token.  |

##### *Body:*

```json5
{
    "mail_type_category_id": 5, // Integer, required; Reference to mail type category.
    "priority": 100, // Integer, required; Priority of newsletter during sending. Higher number is prioritized in queue.
    "code": "premium_newsletter", // String, required; URL-friendly slug identifying mail type
    "title": "Foo Bar", // String, required: Title of mail type
    "description": "Newsletter sent to our premium subscribers", // String, required: Description of list visible in Mailer admin
    "mail_from": "email@example.com", // String, optional; Who should be used as a sender of email type.
    "sorting": 100, // Integer, optional; Indicator of how the mail types should be sorted in API and web. Sorting is in ascending order.
    "locked": false, // Boolean, optional; Flag indicating whether users should be able to subscribe/unsubscribe from the list (e.g. you want your system emails locked and subscribed for everyone)
    "auto_subscribe": false, // Boolean, optional; Flag indicating whether users should be subscribed to this list automatically
    "public_listing": false, // Boolean, optional; Flag whether the user should see the newsletter. Defaults to false.
    "image_url": "http://example.com/image.jpg", // String, optional; URL of image for frontend UI.
    "preview_url": "http://example.com/demo.html", // String, optional; URL of example newsletter to preview content to users.
    "page_url": "http://example.com/page.html", // String, optional; URL of newsletter title page with description and editions.
    "subscribe_mail_template_code": "generic_newsletter_welcome", // String, optional; Reference to mail template that should be sent to the user as welcome email right after the subscription to the newsletter. Only system emails are supported.
    "unsubscribe_mail_template_code": "generic_newsletter_goodbye", // String, optional; Reference to mail template that should be sent to the user as goodbye email right after the unsubscribing from the newsletter. Only system emails are supported.
}
```

##### *Example:*

```shell
curl -X POST \
  http://mailer.remp.press/api/v1/mailers/mail-type-upsert \
  -H 'Authorization: Bearer XXX' \
  -H 'Content-Type: application/json' \
  -b PHPSESSID=cfa9527535e31a0ccb678f792299b0d2 \
  -d '{
	"mail_type_category_id": 5,
	"priority": 100,
	"code": "foo-bar",
	"title": "Foo Bar",
	"description": "Testing list"
}'
```

Response:

```json5
{
    "status": "ok",
    "data": {
        "id": 23,
        "code": "foo-bar",
        "title": "Foo Bar",
        "sorting": 15,
        "description": null,
        "mail_from": null,
        "priority": 100,
        "mail_type_category_id": 5,
        "locked": false,
        "public_listing": true,
        "auto_subscribe": false,
        "image_url": null,
        "preview_url": null,
        "page_url": null,
        "created_at": "2019-06-27T14:08:25+02:00",
        "updated_at": "2019-06-27T14:08:36+02:00",
        "is_multi_variant": false,
        "default_variant_id": null,
        "subscribe_mail_template_code": null,
        "unsubscribe_mail_template_code": null
    }
}
```

---

#### GET `/api/v1/mailers/mail-templates`

Get available mail templates. Possible filtering by `mail_type_code` to get only emails belonging to specified newsletter lists.

##### *Headers:*

| Name          | Value           | Required | Description |
|---------------|-----------------|----------|-------------|
| Authorization | Bearer *String* | yes      | API token.  |

##### *Example:*

```shell
curl -X GET \
  http://mailer.remp.press/api/v1/mailers/mail-templates \
  -H 'Authorization: Bearer XXX'
```

Response:

```json5
[
  {
      "code": "email_1",
      "name": "Welcome email",
      "description": "Email sent after new registration",
      "mail_type_code": "system"
  },
  {
      "code": "email_2",
      "name": "Reset password",
      "description": "Email sent after new password was requested",
      "mail_type_code": "system"
  }
]
```
---

#### GET `/api/v1/mailers/templates`

Gets list of available email templates.

##### *Headers:*

| Name          | Value           | Required | Description |
|---------------|-----------------|----------|-------------|
| Authorization | Bearer *String* | yes      | API token.  |

##### *Params:*

| Name            | Value      | Required | Description                                                                                      |
|-----------------|------------|----------|--------------------------------------------------------------------------------------------------|
| codes           | *String[]* | no       | If provided, list only email templates for given mail_template codes.                            |
| mail_type_codes | *String[]* | no       | If provided, list only email templates for given mail_type codes.                                |
| with_mail_types | *Boolean*  | no       | If true, each returned email template contains additional parameters about assigned mail_type.   |
| page            | *Integer*  | no       | Pagination. Select which page to return. Required if with `limit` parameter is used.             |
| limit           | *Integer*  | no       | Pagination. Limit number of records returned for one page. Required if `page` parameter is used. |

##### *Example:*

```shell
curl --location --request GET \
'mailer.remp.press/api/v1/mailers/templates?mail_type_codes[]=onboarding&mail_type_codes[]=system&with_mail_types=true&page=1&limit=5' \
--header 'Content-Type: application/x-www-form-urlencoded' \
--header 'Accept: application/json' \
--header 'Authorization: Bearer XXX'
```

Response:

```json5
[
    {
        "code": "reset_password",
        "name": "Password reset",
        "mail_type_code": "system",
        "attachments_enabled": true,
        "mail_type": {
            "code": "system",
            "title": "System emails",
            "description": "important notifications",
            "sorting": 1
        }
    }
]
```
---

#### POST `/api/v1/mailers/templates`

Creates new email template. Endpoint complements creation of template via web interface.

##### *Headers:*

| Name          | Value           | Required | Description |
|---------------|-----------------|----------|-------------|
| Authorization | Bearer *String* | yes      | API token.  |

##### *Params:*

| Name           | Value     | Required | Description                                                                                                                                                                                                              |
|----------------|-----------|----------|--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| name           | *String*  | yes      | User-friendly name of the email. It's displayed only in the administration parts of the system.                                                                                                                          |
| code           | *String*  | yes      | Computer-friendly name of the email (slug). Primarily being used when referencing single email that's being sent manually.                                                                                               |
| description    | *String*  | yes      | Internal description, so you know even after a year what the purpose of email was.                                                                                                                                       |
| mail_layout_id | *String*  | yes      | ID of layout to be used for email. If you're providing full HTML/text content, we recommend creating "empty" layout only with *content* within body.                                                                     |
| mail_type_code | *String*  | yes      | Code of newsletter list the email should belong to. Before the email is sent to specific end-user, Mailer checks whether the user is subscribed to this newsletter or not. If he/she is not, the email will not be sent. |
| from           | *String*  | yes      | Who should be used as a sender of email.                                                                                                                                                                                 |
| subject        | *String*  | yes      | Email subject.                                                                                                                                                                                                           |
| template_text  | *String*  | yes      | Text version used as a fallback by email clients.                                                                                                                                                                        |
| template_html  | *String*  | yes      | HTML (primary) version of email that people will see. HTML version is being previewed in the form for creation of new email.                                                                                             |
| click_tracking | *Boolean* | no       | Boolean flag to determine whether click tracking should be attempted on created template. If not provided, system's default settings is used.                                                                            |
| extras         | *String*  | no       | JSON-encoded arbitrary metadata used internally for email personalization and just-in-time (per-user when sending) email content injection                                                                               |

##### *Example:*

```shell
curl -X POST \
  http://mailer.remp.press/api/v1/mailers/templates \
  -H 'Authorization: Bearer XXX' \
  -H 'Content-Type: application/x-www-form-urlencoded' \
  -d 'name=Breaking%20News%20-%20Trump%20elected%20president&code=20161108_breaking_news_trump_elected_president&description=Generated%20by%20CLI%20script&mail_layout_id=1&mail_type_code=alerts&from=info%40dennikn.sk&subject=Breaking%20News%20-%20Trump%20elected%20president&template_text=This%20is%20a%20demo%20text%20of%20email&template_html=%3Cp%3EThis%20is%20a%20%3Cstrong%3Edemo%3C%2Fstrong%3E%20text%20of%20email%3C%2Fp%3E'
```

Response:

```json5
{
    "status": "ok",
    "id": 24832, // Integer; ID of created email
    "code": "20161108_breaking_news_trump_elected_president" // String; code of created email
}
```

---

#### GET `/api/v1/mailers/generator-templates`

Endpoint generates HTML and text content of email based on the selected *generator template* and provided arbitrary
parameters based on the used *generator*. It complements generation of HTML/text content via web interface.

##### *Headers:*

| Name          | Value           | Required | Description |
|---------------|-----------------|----------|-------------|
| Authorization | Bearer *String* | yes      | API token.  |

##### *Example:*

```shell
curl -X GET \
  http://mailer.remp.press/api/v1/mailers/generator-templates \
  -H 'Authorization: Bearer XXX' \
  -H 'Content-Type: application/x-www-form-urlencoded'
```

Response:

```json5
{
    "status": "ok",
    "data": [ // Array; list of available generator templates
        {
            "id": 6, // Integer; ID of generator template
            "title": "Breaking news - alert" // String; user-friendly name of the generator template
        }
        // ...
    ]
}
```

---

#### POST `/api/v1/mailers/generate-mail`

Endpoint generates HTML and text content of email based on the selected *generator template* and provided arbitrary
parameters based on the used *generator*. It complements generation of HTML/text content via web interface.

##### *Headers:*

| Name          | Value           | Required | Description |
|---------------|-----------------|----------|-------------|
| Authorization | Bearer *String* | yes      | API token.  |

##### *Params:*

| Name                 | Value    | Required                 | Description                              |
|----------------------|----------|--------------------------|------------------------------------------|
| source_template_id   | *String* | yes if CODE not provided | ID of *generator template* to be used.   |
| source_template_code | *String* | yes if ID not provided   | CODE of *generator template* to be used. |

Any other parameters are specific to each generator and require knowledge of the generator implementation.
See `apiParams()` method of the generator for the list of available/required parameters.

##### *Example:*

The command uses *generator template* linked to the *UrlParserGenerator*.

```shell
curl -X POST \
  http://mailer.remp.press/api/v1/mailers/generate-mail \
  -H 'Authorization: Bearer XXX' \
  -H 'Content-Type: application/x-www-form-urlencoded' \
  -d 'source_template_id=17&articles=https%3A%2F%2Fdennikn.sk%2F1405858%2Fkedysi-bojovala-za-mier-v-severnom-irsku-teraz-chce-zastavit-brexit%2F%3Fref%3Dtit%0Ahttps%3A%2F%2Fdennikn.sk%2F1406263%2Fpodpora-caputovej-je-tazky-hriech-tvrdil-arcibiskup-predstavitelia-cirkvi-odsudili-aj-radicovu-pred-desiatimi-rokmi%2F%3Fref%3Dtit&footer=%20&intro=%20&rtm_campaign=%20'
```

Response:

```json5
{
  "status": "ok",
  "data": {
    "htmlContent": " -- generated HTML content of email", // String; generated HTML email
    "textContent": " -- generated text content of email " // String; generated plain text email
  }
}
```

---

#### GET `/api/v1/mailers/render-template`

Gets rendered email content by code. Both HTML and text variants are provided. 

##### *Headers:*

| Name          | Value           | Required | Description |
|---------------|-----------------|----------|-------------|
| Authorization | Bearer *String* | yes      | API token.  |

##### *Params:*

| Name | Value    | Required | Description                   |
|------|----------|----------|-------------------------------|
| code | *String* | yes      | `Code` of template to render. |

##### *Example:*

```shell
curl --location --request GET \
'mailer.remp.press/api/v1/mailers/render-template?code=demo-email' \
--header 'Content-Type: application/x-www-form-urlencoded' \
--header 'Accept: application/json' \
--header 'Authorization: Bearer XXX'
```
Response:

```json5
{
    "status": "ok",
    "html": "<Rendered HTML of selected template>",
    "text": "<Rendered Text of selected template>",
}
```

---

#### GET `/api/v1/segments/list`

Lists all available segments that can be used in *jobs*.

##### *Headers:*

| Name          | Value           | Required | Description |
|---------------|-----------------|----------|-------------|
| Authorization | Bearer *String* | yes      | API token.  |

##### *Example:*

```shell
curl -X GET \
  http://mailer.remp.press/api/v1/segments/list \
  -H 'Authorization: Bearer XXX'
```

Response:

```json5
{
    "status": "ok",
    "data": [ // Array; list of available segments
        {
            "name": "Všetci používatelia", // String; User-friendly label of segment
            "code": "all_users", // String; Machine-friendly code of segment (slug)
            "provider": "crm-segment" // String; Provider (owning party) of segment
        }
        // ...
    ]
}
```

---

#### POST `/api/v1/mailers/jobs`

Creates a new sending job with single batch configured to be sent immediately and to randomized list of emails. The job
is automatically in *ready* state indicating to processing daemon that it should be sent right away.

Endpoint complements manual job creation via web interface.

##### *Headers:*

| Name          | Value           | Required | Description |
|---------------|-----------------|----------|-------------|
| Authorization | Bearer *String* | yes      | API token.  |

##### *Params:*

| Name                   | Value    | Required | Description                                         |
|------------------------|----------|----------|-----------------------------------------------------|
| segment_code           | *String* | yes      | Code of the segment to be used.                     |
| segment_provider       | *String* | yes      | Segment provider owning the segment.                |
| template_id            | *String* | yes      | ID of *email*.                                      |
| context                | *String* | no       | Context to be used.                                 |
| mail_type_variant_code | *String* | no       | Specify mail type variant code to be used.          |
| start_at               | *String* | no       | RFC 3339 date format; Specify the start of sending. |

##### *Example:*

```shell
curl -X POST \
  http://mailer.remp.press/api/v1/mailers/jobs \
  -H 'Authorization: Bearer XXX' \
  -H 'Content-Type: application/x-www-form-urlencoded' \
  -d 'template_id=24832&segment_code=users_with_print_in_past&segment_provider=crm-segment&context=123&mail_type_variant_code=variant-1&start_at=2023-12-29T10:05:00Z'
```

Response:

```json5
{
    "status": "ok",
    "id": 24066 // Integer; ID of created job
}
```

---

#### POST `/api/v1/mailers/preprocess-generator-parameters`

Parses arbitrary input - usually data as they're provided by 3rd party (e.g. Wordpress) and returns generator parameters
usable to submit to generator (either via API or web).

See [`preprocessParameters()` bullet of Implementing Generator section](#implementing-generator) for integration example.

##### *Headers:*

| Name          | Value           | Required | Description |
|---------------|-----------------|----------|-------------|
| Authorization | Bearer *String* | yes      | API token.  |

##### *Body:*

```json5
{
    "source_template_id": 17, // Integer; referencing generator template ID
    "data": { // Object; arbitrary data for generator to evaluate and process
        // ...
    }
}
```

##### *Example:*

Following example uses `NewsfilterGenerator` which expects values from Wordpress post on the input. Preprocessing is
expecting JSON-encoded instance of Wordpress post. We've included only necessary parameters to show the transformation
that generator makes.

```shell
curl -X POST \
  http://mailer.remp.press/api/v1/mailers/preprocess-generator-parameters \
  -H 'Authorization: Bearer XXX' \
  -H 'Content-Type: application/json' \
  -d '{
	"source_template_id": 20,
	"data": {
		"post_title": "Example article",
		"post_url": "https://www.example.com/article",
		"post_excerpt": "Lorem ipsum...",
		"post_content": " -- Wordpress text content --",
		"post_authors": [
			{
				"display_name": "Admin Admin"
			}
		]
	}
}'
```

Response:

```json5
{
    "status": "ok",
    "data": {
        "editor": "Admin Admin",
        "title": "Example article",
        "url": "https://www.example.com/article",
        "summary": "Lorem ipsum...",
        "newsfilter_html": " -- Wordpress text content --",
        "source_template_id": 20
    },
    "generator_post_url": "http://mailer.remp.press/mail-generator/"
}
```

Transformed `data` can be then used as parameters of `/api/v1/mailers/generate-mail` endpoint.

As of the writing of this description, not all generators are required to provide preprocessing of parameters.
It's a responsibility of the caller to know whether the source template uses generator that can *preprocess* parameters.
If the *preprocess* is called for a generator not supporting it, *HTTP 400 Bad Request* is returned with error message.

---

#### POST `/api/v1/mailers/mailgun`

Webhook endpoint for legacy Mailgun event reporting. We advise using `v2` of this endpoint and new implementation of webhooks on Mailgun.

You can configure Mailgun webhooks in the Mailgun's [control panel](https://app.mailgun.com/app/webhooks). For more information about Mailgun webhooks, please check the [documentation](https://documentation.mailgun.com/en/latest/user_manual.html#webhooks), [quick guide](https://www.mailgun.com/blog/a-guide-to-using-mailguns-webhooks) or [guide to webhooks](https://www.mailgun.com/guides/your-guide-to-webhooks).

Webhook configuration should be enough to enable the tracking in Mailer. Following example is displayed primarily for testing purposes and completeness.

##### *Params:*

| Name           | Value    | Required | Description                                   |
|----------------|----------|----------|-----------------------------------------------|
| mail_sender_id | *String* | yes      | Back-reference to specific email Mailer sent. |
| timestamp      | *String* | yes      | Timestamp when event occurred.                |
| token          | *String* | yes      | Verification field.                           |
| signature      | *String* | yes      | Verification field.                           |
| recipient      | *String* | yes      | Email address of recipient.                   |
| event          | *String* | yes      | Type of email that occurred.                  |

##### *Example:*

The example serves only for debugging purposes, you shouldn't really need to call it yourself.

```shell
curl -X POST \
  http://mailer.remp.press/api/v1/mailers/mailgun \
  -H 'Authorization: Bearer XXX' \
  -H 'Content-Type: application/x-www-form-urlencoded' \
  -d 'mail_sender_id=foo&timestamp=1529006854&token=a8ce0edb2dd8301dee6c2405235584e45aa91d1e9f979f3de0&signature=d2271d12299f6592d9d44cd9d250f0704e4674c30d79d07c47a66f95ce71cf55&recipient=admin%40example.com&event=opened'
```

Response:

```json5
{
    "status": "ok",
}
```

The event itself is just validated and put to the asynchronous queue to be processed later.

---

#### POST `/api/v2/mailers/mailgun`

Webhook endpoint for new Mailgun event reporting. Comparing to `v1` the payload provided by Mailgun is more descriptive
and is sent as a JSON body instead of HTTP form parameters.

You can configure Mailgun webhooks in the Mailgun's [control panel](https://app.mailgun.com/app/webhooks). For more
information about Mailgun webhooks, please check the [documentation](https://documentation.mailgun.com/en/latest/user_manual.html#webhooks)
, [quick guide](https://www.mailgun.com/blog/a-guide-to-using-mailguns-webhooks) or [guide to webhooks](https://www.mailgun.com/guides/your-guide-to-webhooks).

Webhook configuration should be enough to enable the tracking in Mailer. Following example is displayed primarily for
testing purposes and completeness.

##### *Body:*

```json5
{
  "signature": {
    "timestamp": "1529006854",
    "token": "a8ce0edb2dd8301dee6c2405235584e45aa91d1e9f979f3de0",
    "signature": "d2271d12299f6592d9d44cd9d250f0704e4674c30d79d07c47a66f95ce71cf55"
  },
  "event-data": {
    "event": "opened",
    "timestamp": 1529006854.329574,
    "id": "DACSsAdVSeGpLid7TN03WA",
    // ...
  }
}
```

##### *Example:*

The example serves only for debugging purposes, you shouldn't really need to call it yourself. More verbose of example
can be found in [Mailgun's blogpost](https://www.mailgun.com/blog/same-api-new-tricks-get-event-notifications-just-in-time-with-webhooks)
introducing new version of webhooks.

```shell
curl -X POST \
  http://mailer.remp.press/api/v2/mailers/mailgun \
  -H 'Authorization: Bearer XXX' \
  -H 'Content-Type: application/json' \
  -d '{
  "signature": {
    "timestamp": "1529006854",
    "token": "a8ce0edb2dd8301dee6c2405235584e45aa91d1e9f979f3de0",
    "signature": "d2271d12299f6592d9d44cd9d250f0704e4674c30d79d07c47a66f95ce71cf55"
  },
  "event-data": {
    "event": "opened",
    "timestamp": 1529006854.329574,
    "id": "DACSsAdVSeGpLid7TN03WA"
  }
}'
```

Response:

```json5
{
    "status": "ok",
}
```

The event itself is just validated and put to the asynchronous queue to be processed later.

---

#### POST `/api/v1/mailers/send-email`

Endpoint for sending single email without creating a job. It should be primarily used for system (event based) emails and testing emails.

##### *Body:*

```json5
{
  "mail_template_code": "welcome_email_with_password",
  "email": "admin@example.com",
  "params": { // optional: key-value string pairs of parameters to be used mail_template variables
    "email": "admin@example.com",
    "password": "secret"
  },
  "context": "user.welcome.123", // optional: if email with the same context and mail_template_code was already sent, Mailer will not send the email again
  "attachments": [ // optional
    {
      "file": "/path/to/file", // used to determine name of attachment and possibly content of attachment
      "content": "-- base64 encoded content of attachment --" // if content is not provided, Mailer attempts to open file based on provided path in "file" property
    }
  ],
  "schedule_at": "2019-09-23T08:50:03+00:00", // optional: RFC3339-formatted date when email should be sent; if not provided, email is scheduled to be sent immediately
  "locale": "en" // optional: specify language version of email
}
```

##### *Example:*


```shell
curl -X POST \
  http://mailer.remp.press/api/v1/mailers/send-email \
  -H 'Authorization: Bearer XXX' \
  -H 'Content-Type: application/json' \
  -d '{
	"mail_template_code": "welcome_email_with_password",
	"email": "admin@example.com"
}
```

Response:

```json5
{
    "status": "ok",
    "message": "Email was scheduled to be sent."
}
```

Actual sending is being processed asynchronously by background workers and might be delayed based on available system resources and size of the background processing queue.

---

#### POST `/api/v1/mailers/mail-type-variants`

Creates new mail type variant.

##### *Body:*

```json5
{
  "mail_type_code": "type-25",
  "title": "Variant 1",
  "code": "variant-1",
  "sorting": 100
}
```

##### *Example:*

```shell
curl -X POST \
  http://mailer.remp.press/api/v1/mailers/mail-type-variants \
  -H 'Authorization: Bearer XXX' \
  -H 'Content-Type: application/json' \
  -d '{
	"mail_type_code": "type-25",
	"title": "Variant 1",
	"code": "variant-1",
	"sorting": 100
}
```

Response:

```json5
{
    "status": "ok",
    "id": 24066 // Integer; ID of created mail type variant
}
```
