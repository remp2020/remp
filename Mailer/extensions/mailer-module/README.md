# Mailer Module

Mailer Module is a core extensible package of the REMP Mailer, which serves as a tool for configuration of mailers, creation of email layouts and
templates, and configuring and sending mail jobs to selected segments of users.

**Note:** To quickstart with fully configured Mailer app, please check out the Mailer-Skeleton repository.

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

### Configuration

All required configuration options are described in `.env.example` file. Please copy its content into `.env` file in of your project.

## API Documentation

All examples use `http://mailer.remp.press` as a base domain. Please change the host to the one you use
before executing the examples.

All examples use `XXX` as a default value for authorization token, please replace it with the
real token API token which can be acquired in the REMP SSO.

API responses can contain following HTTP codes:

| Value | Description |
| --- | --- |
| 200 OK | Successful response, default value |
| 400 Bad Request | Invalid request (missing required parameters) |
| 403 Forbidden | The authorization failed (provided token was not valid) |
| 404 Not found | Referenced resource wasn't found |

If possible, the response includes `application/json` encoded payload with a message explaining
the error further.

---

#### POST `/api/v1/users/user-registered`

When user is registered, Mailer should be notified so it can start tracking newsletter subscription for this new email
address. This new email address will be automatically subscribed to any newsletter that has enabled *auto subscribe*
option.

##### *Headers:*

| Name | Value | Required | Description |
| --- |---| --- | --- |
| Authorization | Bearer *String* | yes | API token. |

##### *Params:*

| Name | Value | Required | Description |
| --- |---| --- | --- |
| email | *String* | yes | Email address of user. |
| user_id | *String/Integer* _(validated by FILTER_VALIDATE_INT)_ | yes | ID of user. |

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

| Name | Value | Required | Description |
| --- |---| --- | --- |
| Authorization | Bearer *String* | yes | API token. |

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

| Name | Value | Required | Description |
| --- |---| --- | --- |
| email | *String* | yes | Email address of user. |
| user_id | *String/Integer* _(validated by FILTER_VALIDATE_INT)_ | yes | ID of user. |

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

| Name | Value | Required | Description |
| --- |---| --- | --- |
| Authorization | Bearer *String* | yes | API token. |

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
true
```

---

#### POST `/api/v1/users/user-preferences`

API call to get subscribed newsletter lists and their variants.

##### *Headers:*

| Name | Value | Required | Description |
| --- |---| --- | --- |
| Authorization | Bearer *String* | yes | API token. |

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

API call subscribes email address to the given newsletter. Newsletter has tp be already created.
Currently, there's no API endpoint for that and the newsletter needs to be created manually.
Please visit `/list/new` to create a newsletter via web admin.

##### *Headers:*

| Name | Value | Required | Description |
| --- |---| --- | --- |
| Authorization | Bearer *String* | yes | API token. |

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
	"variant_id": 1
}'
```

Response:

```json5
{
    "status": "ok"
}
```

---

#### POST `/api/v1/users/un-subscribe`

API call unsubscribes email address from the given newsletter.

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

| Name | Value | Required | Description |
| --- |---| --- | --- |
| Authorization | Bearer *String* | yes | API token. |

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

    // optional RTM parameters for tracking "what" made the user unsubscribe
    "rtm_params": { // Object; optional RTM parameters for pairing which email caused the user to unsubscribe. RTM params are generated into the email links automatically.
        "rtm_source": "newsletter_daily",
        "rtm_medium": "email",
        "rtm_campaign": "daily-newsletter-11.3.2019-personalized",
        "rtm_content": "26026"
    },

    "utm_params": { // (Deprecated) Fallback if no RTM parameters are found
        "utm_source": "newsletter_daily",
        "utm_medium": "email",
        "utm_campaign": "daily-newsletter-11.3.2019-personalized",
        "utm_content": "26026"
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

| Name | Value | Required | Description |
| --- |---| --- | --- |
| Authorization | Bearer *String* | yes | API token. |

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

      "subscribe": false, // Boolean; indicates if you want to subscribe or unsubscribe user

      // optional RTM parameters used only if `subscribe:false` for tracking "what" made the user unsubscribe
      "rtm_params": { // Object; optional RTM parameters for pairing which email caused the user to unsubscribe. RTM params are generated into the email links automatically.
        "rtm_source": "newsletter_daily",
        "rtm_medium": "email",
        "rtm_campaign": "daily-newsletter-11.3.2019-personalized",
        "rtm_content": "26026"
      },

      "utm_params": { // Fallback to UTM - deprecated option, will be removed
        "utm_source": "newsletter_daily",
        "utm_medium": "email",
        "utm_campaign": "daily-newsletter-11.3.2019-personalized",
        "utm_content": "26026"
      }
    }
  //...
  ]
}
```

###### *Properties of one users element*

| Name | Value | Required | Description |
| --- |---| --- | --- |
| email | *String* | yes | Email address of user. |
| user_id | *String/Integer* _(validated by FILTER_VALIDATE_INT)_ | yes | ID of user. |
| subscribe | *Boolean* | yes | Flag to indicate if user should subscribed or un-subscribed. |
| list_id | *Integer* | yes _(use list_id or list_code)_ | ID of mail list. |
| list_code | *String* | yes _(use list_id or list_code)_ | Code of mail list. |
| variant_id | *Integer* | no | Optional ID of variant. |
| rtm_params | *Object* | no | Optional RTM parameters for pairing which email caused the user to unsubscribe. |
| utm_params | *Object* | no | (Deprecated) UTM parameters are deprecated, but if no RTM paramters are found, system will try to use these. |


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

| Name | Value | Required | Description |
| --- |---| --- | --- |
| Authorization | Bearer *String* | yes | API token. |

##### *Params:*

| Name | Value | Required | Description |
| --- |---| --- | --- |
| original_email | *String* | yes | Original email address of user. |
| new_email | *String* | yes | New email address of user. |

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

| Name | Value | Required | Description |
| --- |---| --- | --- |
| Authorization | Bearer *String* | yes | API token. |

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

Returns mail logs based on given criteria

##### *Headers:*

| Name | Value | Required | Description |
| --- |---| --- | --- |
| Authorization | Bearer *String* | yes | API token. |

##### *Body:*

```json5
{
  //required
  "email": "test@test.com", // String; email

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


#### GET `/api/v1/mailers/mail-types`

Lists all available *newsletter lists* (mail types). *Code* of the newsletter is required when creating
new *email* template via API.

##### *Headers:*

| Name | Value | Required | Description |
| --- |---| --- | --- |
| Authorization | Bearer *String* | yes | API token. |

##### *Params:*

| Name | Value | Required | Description |
| --- |---| --- | --- |
| code | *String* | no | Filter only newsletter (mail type) with specific code. Returns array with either 0 or 1 element. |
| public_listing | *Boolean* | no | Flag whether only newsletters (mail types) hat should/shouldn't be available to be listed publicly should be returned. |

##### *Example:*

```shell
curl -X GET \
  http://mailer.remp.press/api/v1/mailers/mail-types?public_listing=1 \
  -H 'Authorization: Bearer XXX'
```

Response:

```json5
{
  "status": "ok",
  "data": [
    {
      "id": 2,
      "code": "123",
      "image_url": "",
      "preview_url": "",
      "page_url": "",
      "title": "Test",
      "description": "",
      "locked": false,
      "is_multi_variant": true,
      "variants": {
        "4": "test2",
        "5": "test4"
      }
    },
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

#### GET `/api/v1/mailers/mail-type-categories`

Get available categories of newsletters.

##### *Headers:*

| Name | Value | Required | Description |
| --- |---| --- | --- |
| Authorization | Bearer *String* | yes | API token. |

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
    "show_title": true
  },
  {
    "id": 2,
    "title": "System",
    "sorting": 999,
    "show_title": false
  }
]
```

---

#### POST `/api/v1/mailers/mail-type-upsert`

Creates or updates mail type (newsletter list). Endpoint complements creation of newsletter list via web interface.

If existing `id`/`code` is provided, API handler updates existing record, otherwise a record is created.
Field `id` has higher precedence in finding the existing record.

##### *Headers:*

| Name | Value | Required | Description |
| --- |---| --- | --- |
| Authorization | Bearer *String* | yes | API token. |

##### *Body:*

```json5
{
    "mail_type_category_id": 5, // Integer, required; Reference to mail type category.
    "priority": 100, // Integer, required; Priority of newsletter during sending. Higher number is prioritized in queue.
    "code": 22, // String, required; URL-friendly slug identifying mail type
    "title": "Foo Bar", // String, required: Title of mail type
    "description": "Newsletter sent to our premium subscribers", // String, required: Description of list visible in Mailer admin
    "mail_from": "email@example.com", // String, optional; Who should be used as a sender of email type.
    "sorting": 100, // Integer, optional; Indicator of how the mail types should be sorted in API and web. Sorting is in ascending order.
    "locked": false, // Boolean, optional; Flag indicating whether users should be able to subscribe/unsubscribe from the list (e.g. you want your system emails locked and subscribed for everyone)
    "auto_subscribe": false, // Boolean, optional; Flag indicating whether users should be subscribed to this list automatically
    "is_public": false, // Boolean, optional; Flag whether the list should be available in Mailer admin for selection. Defaults to true.
    "public_listing": false, // Boolean, optional; Flag whether the user should see the newsletter. Defaults to false.
    "image_url": "http://example.com/image.jpg", // String, optional; URL of image for frontend UI.
    "preview_url": "http://example.com/demo.html", // String, optional; URL of example newsletter to preview content to users.
    "page_url": "http://example.com/page.html", // String, optional; URL of newsletter title page with description and editions.
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
        "is_public": false,
        "public_listing": true,
        "auto_subscribe": false,
        "image_url": null,
        "preview_url": null,
        "page_url": null,
        "created_at": "2019-06-27T14:08:25+02:00",
        "updated_at": "2019-06-27T14:08:36+02:00",
        "is_multi_variant": false,
        "default_variant_id": null
    }
}
```

---

#### GET `/api/v1/mailers/mail-templates`

Get available mail templates. Possible filtering by `mail_type_code` to get only emails belonging to specified newsletter lists.

##### *Headers:*

| Name | Value | Required | Description |
| --- |---| --- | --- |
| Authorization | Bearer *String* | yes | API token. |

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

| Name | Value | Required | Description |
| --- |---| --- | --- |
| Authorization | Bearer *String* | yes | API token. |

##### *Params:*

| Name | Value | Required | Description |
| --- |---| --- | --- |
| codes | *String[]* | no | If provided, list only email templates for given mail_template codes.
| mail_type_codes | *String[]* | no | If provided, list only email templates for given mail_type codes.
| with_mail_types | *Boolean* | no | If true, each returned email template contains additional parameters about assigned mail_type.
| page | *Integer* | no | Pagination. Select which page to return. Required if with `limit` parameter is used.  
| limit | *Integer* | no | Pagination. Limit number of records returned for one page. Required if `page` parameter is used.

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

| Name | Value | Required | Description |
| --- |---| --- | --- |
| Authorization | Bearer *String* | yes | API token. |

##### *Params:*

| Name | Value | Required | Description |
| --- |---| --- | --- |
| name | *String* | yes | User-friendly name of the email. It's displayed only in the administration parts of the system. |
| code | *String* | yes | Computer-friendly name of the email (slug). Primarily being used when referencing single email that's being sent manually. |
| description | *String* | yes | Internal description, so you know even after a year what the purpose of email was. |
| mail_layout_id | *String* | yes | ID of layout to be used for email. If you're providing full HTML/text content, we recommend creating "empty" layout only with *content* within body. |
| mail_type_code | *String* | yes | Code of newsletter list the email should belong to. Before the email is sent to specific end-user, Mailer checks whether the user is subscribed to this newsletter or not. If he/she is not, the email will not be sent. |
| from | *String* | yes | Who should be used as a sender of email. |
| subject | *String* | yes | Email subject. |
| template_text | *String* | yes | Text version used as a fallback by email clients. |
| template_html | *String* | yes | HTML (primary) version of email that people will see. HTML version is being previewed in the form for creation of new email. |
| click_tracking | *Boolean* | no | Boolean flag to determine whether click tracking should be attempted on created template. If not provided, system's default settings is used. |
| extras | *String* | no | JSON-encoded arbitrary metadata used internally for email personalization and just-in-time (per-user when sending) email content injection |

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

| Name | Value | Required | Description |
| --- |---| --- | --- |
| Authorization | Bearer *String* | yes | API token. |

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

| Name | Value | Required | Description |
| --- |---| --- | --- |
| Authorization | Bearer *String* | yes | API token. |

##### *Params:*

| Name | Value | Required | Description |
| --- |---| --- | --- |
| source_template_id | *String* | yes if CODE not provided  | ID of *generator template* to be used. |
| source_template_code | *String* | yes if ID not provided | CODE of *generator template* to be used. |

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

| Name | Value | Required | Description |
| --- |---| --- | --- |
| Authorization | Bearer *String* | yes | API token. |

##### *Params:*

| Name | Value | Required | Description |
| --- |---| --- | --- |
| code | *String* | yes | `Code` of template to render.

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

| Name | Value | Required | Description |
| --- |---| --- | --- |
| Authorization | Bearer *String* | yes | API token. |

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

| Name | Value | Required | Description |
| --- |---| --- | --- |
| Authorization | Bearer *String* | yes | API token. |

##### *Params:*

| Name | Value | Required | Description |
| --- |---| --- | --- |
| segment_code | *String* | yes | Code of the segment to be used. |
| segment_provider | *String* | yes | Segment provider owning the segment. |
| template_id | *String* | yes | ID of *email*. |
| context | *String* | no | Context to be used. |
| mail_type_variant_code | *String* | no | Specify mail type variant code to be used. |

##### *Example:*

```shell
curl -X POST \
  http://mailer.remp.press/api/v1/mailers/jobs \
  -H 'Authorization: Bearer XXX' \
  -H 'Content-Type: application/x-www-form-urlencoded' \
  -d 'template_id=24832&segment_code=users_with_print_in_past&segment_provider=crm-segment&context=123&mail_type_variant_code=variant-1'
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

| Name | Value | Required | Description |
| --- |---| --- | --- |
| Authorization | Bearer *String* | yes | API token. |

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

Webhook endpoint for legacy Mailgun event reporting. We advise using `v2` of this endpoint and new implementation
of webhooks on Mailgun.

You can configure Mailgun webhooks in the Mailgun's [control panel](https://app.mailgun.com/app/webhooks). For more
information about Mailgun webhooks, please check the [documentation](https://documentation.mailgun.com/en/latest/user_manual.html#webhooks)
, [quick guide](https://www.mailgun.com/blog/a-guide-to-using-mailguns-webhooks) or [guide to webhooks](https://www.mailgun.com/guides/your-guide-to-webhooks).

Webhook configuration should be enough to enable the tracking in Mailer. Following example is displayed primarily for
testing purposes and completeness.

##### *Params:*

| Name | Value | Required | Description |
| --- |---| --- | --- |
| mail_sender_id | *String* | yes | Back-reference to specific email Mailer sent. |
| timestamp | *String* | yes | Timestamp when event occurred. |
| token | *String* | yes | Verification field. |
| signature | *String* | yes | Verification field. |
| recipient | *String* | yes | Email address of recipient. |
| event | *String* | yes | Type of email that occurred. |

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
  "schedule_at": "2019-09-23T08:50:03+00:00" // optional: RFC3339-formatted date when email should be sent; if not provided, email is scheduled to be sent immediately
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
