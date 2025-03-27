# REMP

## Apps

Each of the REMP services provides its own description and integration documentation - you can access
it by clicking one of the headings below.

Following is a brief description of REMP services included in this mono-repository. 

#### [SSO](Sso)

SSO is the single point of authentication in the default REMP tools configuration. Currently, it allows users of
REMP tools (Beam, Campaign, Mailer) to log in via their Google accounts.

It also serves as an authentication tool for API requests across the REMP tools - you can manage your API keys
within the web administration of the SSO.

In the future we plan to make easier to develop other authentication mechanisms to connect and also proper
authorization management for users.

#### [Beam](Beam)

Beam is primary tool for tracking all events across the system and providing aggregated data for statistical
components in Beam and other REMP services.

You can track pageview related events right from the Javascript on your website or call an API from backend.

Beam admin provides a way to display real time usage stats on your website, aggregated article/author/conversion
data and allows you to create user segments based on the tracked data.

#### [Campaign](Campaign)

Campaign is a tool for easy creation and showing of banners on your website. In the banner definition you can
configure how the banner should look like, where it should be displayed and what it should include.

In the campaign definition you can configure who should see the banner and how often.

If Beam is installed, you can use user segments in configuration who should see the banner. You can also link
other segment providers (e.g. your own CRM) to provide other user segments.

#### [Mailer](Mailer)

Mailer provides a way to manage emails (layouts and templates) which can be sent as newsletters. Mailer provides
APIs for managing user's newsletter subscriptions, handles batch sending of emails and A/B testing.

To complement all users subscribed to newsletter, you're able to use user segment when sending a newsletter.
Therefore, only users belonging to the selected segment which are also subscribed to the newsletter will receive
an email. In the default configuration, Beam segments are available. You can link your own segment provider
(e.g. your own CRM) to provide other user segments.

In a default implementation SMTP and Mailgun mailer providers are implemented. You can extend the implementation
with your own Mailer provider if you need it. 


## Install

### Docker installation
*(recommended for development or testing)*

#### 1. Pre-building binaries of Go apps

There's a need for pre-building binaries of Go apps before you can run Docker compose. You don't need Go environment 
to have set up, but you need Docker to build docker-ready tarballs properly.

```bash
make docker-build
```

#### 2. Docker Compose

We've prepared `docker-compose.yml` in a way it's ready for development.

```bash
docker compose up
```

After pulling new code, if changes where made to `Docker/php/Dockerfile` or any `composer.lock` files run:
```bash
docker compose up --build
```

> The appliance was tested with Docker Engine - Community 23.0 and Docker Compose 2.16.0.

##### 2.1 User rights in the container

Since REMP 1.3, Docker Compose appliance supports passing the currently logged user to the containers to make sure there are not any access rights issues within the containers.

To correctly pass information about your user to Docker Compose, create `.env` file in the root of the project (based on the `.env.example`). If the Docker compose cannot find the information, it will default to the `docker` user with UID:GID of 1000:1000.

#### 3. First run

If some application doesn't have `.env` file, docker will assume it's not installed and it will automatically proceed with all required steps to install project. It will:
* create `.env` file,
* install all the dependencies _(composer & yarn)_,
* prepare the DB structure and insert demo data.

You can see what is installed in [Docker/php/remp.sh](Docker/php/remp.sh).

#### 4. Override services & enviroment settings

Feel free to override/add services via `docker-compose.override.yml`.

This is an excerpt of override we use. It handles proper connection of XDebug to host machine, exposing services
running outside of this appliance (such as our internal CRM) and caching of yarn/composer packages.

We highly recommend to place the yarn/composer cache volumes to all PHP-based services, but only after the first run.
Otherwise, the installation of project would be downloading the packages simultaneously and would be overriding stored
packages in cache. This would cause an error.

```yml
version: "3"

services:
  campaign:
    environment:
      XDEBUG_CONFIG: "remote_host=172.17.0.1"
    extra_hosts:
      - "crm.press:172.17.0.1"
    volumes:
      - "/home/developer/.cache/composer:/composer:rw"
      - "/home/developer/.cache/yarn:/yarn:rw"

  mysql:
    volumes:
      - ".:/data"

```

You can also override environment variables in `.env` file of each project. After [first run](#3-first-run) this file contains default values _(copy of `.env.example`)_.

#### 5. Hosts

Application exposes all services via Nginx container.
Following is list of available hosts. We advise you to add them to your
`/etc/hosts`:

```bash
# CAMPAIGN
127.0.0.1 campaign.remp.press # web administration

# MAILER
127.0.0.1 mailer.remp.press # web administration

# BEAM
127.0.0.1 beam.remp.press # web administration
127.0.0.1 tracker.beam.remp.press # event tracker API; swagger @ http://tracker.beam.remp.press/swagger.json
127.0.0.1 segments.beam.remp.press # segments API; swagger @ http://segments.beam.remp.press/swagger.json

# SSO
127.0.0.1 sso.remp.press # web administration and API

# SERVICE APPS
127.0.0.1 adminer.remp.press # adminer for manipulating with MySQL
127.0.0.1 mailhog.remp.press # mailhog for catching and debugging sent emails
127.0.0.1 kibana.beam.remp.press # kibana for manipulating with Elastic data
```

> Note: If you use Docker Toolbox, the IP won't be `127.0.0.1`. Use `docker-machine ls` to get IP address of the machine.

#### 6. Integration

To integrate Beam and Campaign with your website you need to include javascript into your page. Consult part **javascript snippets** within READMEs - [Beam#javascript-snippet](Beam/README.md#javascript-snippet) and [Campaign#javascript-snippet](Campaign/README.md#javascript-snippet).

### Manual installation

Please use `docker-compose.yml` and configuration/scripts within [Docker](./Docker) folder as a reference for manual
installation of each service.

Steps to install dependencies of each project are part of README file for that particular service.

Be aware of `.env.example` files across projects. These need to be copied to `.env` and configured based on your
configuration.

## Docker Compose

If you're unfamiliar with `docker-compose`, try running `docker compose --help` as a starter. Each of the subcommands 
of Docker also supports its own `--help` switch. Feel free to explore it.

Couple of neat commands:
* `docker compose down` to remove all containers, networks and volumes created by `docker-compose`
* `docker compose ps` to list all services with their status
* `docker compose logs` to read services logs
* `docker compose build` to force rebuild of images
* `docker compose exec campaign /bin/bash` to connect to `campaign` container
* `docker images` to list all available docker images

## PHP Debugging

Docker compose and custom images are pre-installed with XDebug and pre-configured for PHPStorm debugger. All you need to do is set folder mapping within
your IDE for each debuggable host.

## Error tracking

All PHP applications are preconfigured to use [Airbrake](https://airbrake.io/) error tracking - see `.env.example`
for configuration options.

We recommend to use self-hosted [Errbit](https://github.com/errbit/errbit) instance
as it's compatible with Airbrake APIs and completely open-source.

## Running behind proxy

You might want to run your application on `https`, but internally use load balancers and forward requests to handlers via `http`.

By default the applications determine protocol of generated URLs based on the incoming request protocol. In scenario described above this would cause `http` links to be generated on `https` page visit.

To enforce `https` generation, you can use `FORCE_HTTPS` environment variable changing the behavior in all web applications. See `.env.example` files for configuration options.

## Known issues

- PHP images are coming with preinstalled and always-on XDebug. We made the always-on choise for easier debugging and
availability to debug also console scripts. However, if it can't connect to the host machine, it slows down the request
because it waits for connection timeout. Therefore, is very important to have proper `XDEBUG_CONFIG` variables
configured in your `docker-compose.override.yml`. 
