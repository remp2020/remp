# Mailer

Mailer serves as a tool for configuration of mailers, creation of email layouts and
templates, and configuring and sending mail jobs to selected segments of users.

**Note:** To quickstart with fully configured Mailer application, please check out the [Mailer-skeleton](https://github.com/remp2020/mailer-skeleton) repository.


## Installation

When the backend is ready, don't forget to install dependencies and run DB migrations:

```bash
# 1. Download PHP dependencies
composer install

# 2. Download JS/HTML dependencies
yarn install

# !. use extra switch if your system doesn't support symlinks (Windows; can be enabled)
yarn install --no-bin-links

# 3. Generate assets
make js

# 4. Run migrations
php bin/command.php migrate:migrate

# 5. Run seeders
php bin/command.php db:seed
php bin/command.php demo:seed # optional
```

You can override any default config from
[`config.neon`](./app/config/config.neon) by creating file
`config.local.neon` and setting your own values.

### Dependencies

- PHP ^8.1
- MySQL ^8.0
- Redis ^6.2
- Node.js >=18

## Documentation

Please see Mailer-module [documentation](./extensions/mailer-module/README.md) for details.
