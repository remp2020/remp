# REMP Mailer

## Installation

If you're using `docker-compose` provided within this repo, we recommend to get inside the container via
`docker-compose exec mailer bash` and running commands there. 

Otherwise you need to install `php7`, `oomposer`, `nodejs` and `yarn` first.

```bash
# 1. Download backend dependencies
composer install

# 2. Download frontend dependencies
yarn install

# !. use extra switch if your system doesn't support symlinks (Windows; can be enabled)
yarn install --no-bin-links

# 3. Generate assets
yarn run dev // or any other alternative defined within package.json
```