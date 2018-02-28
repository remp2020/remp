# SSO

## Admin (Laravel)

SSO Admin serves as a tool for management of API keys.

When the backend is ready, don't forget to create `.env` file (use `.env.example` as boilerplate), install dependencies and run DB migrations:

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
php artisan migrate

# 5. Generate app key and JWT secret
php artisan key:generate
php artisan jwt:secret

```

#### Dependencies

- PHP 7.1
- MySQL 5.7
- Redis 3.2

## Auth endpoints

### GET /auth/login

Endpoint accessible for end users. This is the place where they decide how they want to
get logged in.

#### Required query parameters:

* `succesUrl: string`
  
  Url to which user is redirect after successful login attempt.
  
  SSO appends *token* query parameter to the response. This token should be sent within
  `Authorization: Bearer %TOKEN%` header for all subsequent requests.
  
* `errorUrl: string`

  URL to which user is redirected after unsuccessful login attempt.
  
  SSO appends *error* query parameter with error message explaining why the authentication
  was not successful.
  
### GET /auth/introspect

API endpoint for services to get user information based on the provided token.
  
#### Required headers:
  
* `Authorization: Bearer %TOKEN%`

#### Success response:

* `200 OK`
```
{
  "name": String, // full name of user
  "email": String, // email of user
  "scopes": Array // array of scopes user has access to
}
```

#### Error responses:

HTTP status codes are based on RFC 6750.

* `400 Bad Request`
  * `token_not_provided` error when no token is provided
* `401 Unauthorized`
  * `token_expired` error when token is expired; call `/auth/refresh` to refresh the token 
  * `token_invalid` error when token is unparseable
* `404 Not Found`
  * `user_not_found` error when user encoded within token is not found
  
```
{
  "code": String, // error code
  "detail": String, // error message
  "redirect": String // SSO login URL to redirect user to
}
```
  
### POST /auth/refresh

API endpoint for services to refresh the token in case it's expired. If `JWT_BLACKLIST_ENABLED`
is set to `true` (default value), it automatically invalidates the old token.

#### Required headers:
  
* `Authorization: Bearer %TOKEN%`

#### Success response:

* `200 OK`
```
{
  "token": String, // refreshed token
}
```

#### Error responses:

* `400 Bad Request`
  * `token_not_provided` error when no token is provided 
  * `token_expired` error when token is expired and unrefreshable; default refresh timeout is 2 weeks
  * `token_invalid` error when token is unparseable
* `404 Not Found`
  * `user_not_found` error when user encoded within token is not found

```
{
  "code": String, // error code
  "detail": String, // error message
  "redirect": String // SSO login URL to redirect user to
}
```

### GET /auth/api-token

API endpoint for services to validate provided API token. Endpoint simply returns whether token
is usable or not and no additional info.
  
#### Required headers:
  
* `Authorization: Bearer %TOKEN%`

#### Success response:

* `200 OK`

#### Error responses:

HTTP status codes are based on RFC 6750.

* `404 Not Found`
