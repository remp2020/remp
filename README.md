# REMP

## Apps

See separate documentations of each app:
* [Beam](Beam)
* [Campaign](Campaign)
* [Mailer](Mailer)
* [Sso](Sso)

## Install

 
**1. Pre-building binaries of Go apps**

There's a need for pre-building binaries of Go apps before you can run Docker compose. You don't need Go environment 
to have set up, but you need Docker to build docker-ready tarballs properly.

```bash
make docker-build
```

**2. Docker-compose**

We've prepared `docker-compose.yml` in a way it's ready for development.
```bash
docker-compose up
```

Feel free to override/add services via `docker-compose.override.yml`.

This is an excerpt of override we use. It handles proper connection of XDebug to host machine, exposing services
running outside of this appliance (such as our internal CRM) and caching of yarn/composer packages.

We highly recommend to place the yarn/composer cache volumes to all PHP-based services, but only after the first run.
Otherwise the installation of project would be downloading the packages simultaneously and would be overriding stored
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
127.0.0.1 grafana.beam.remp.press # grafana for manipulating with InfluxDB and displaying charts
```

Note: If you use Docker Toolbox, the IP won't be `127.0.0.1`. Use `docker-machine ls` to get IP address of the machine.

Docker will install all the dependencies, prepares the DB structure and also inserts demo data.

The appliance was tested with Docker CE 17.12.0 and Docker Compose 1.16.1.

## Manual installation

Please use `docker-compose.yml` and configuration/scripts within [Docker](./Docker) folder as a reference for manual
installation of each service.

Be aware of `.env.example` files across projects. These need to be copied to `.env` and configured based on your
configuration.

### Docker Compose

If you're unfamiliar with `docker-compose`, try running `docker-compose --help` as a starter. Each of the subcommands 
of Docker also supports its own `--help` switch. Feel free to explore it.

Couple of neat commands:
* `docker-compose down` to remove all containers, networks and volumes created by `docker-compose`
* `docker-compose ps` to list all services with their status
* `docker-compose logs` to read services logs
* `docker-compose build` to force rebuild of images
* `docker-compose exec campaign /bin/bash` to connect to `campaign` container
* `docker images` to list all available docker images

## PHP Debugging

Docker compose and custom images are pre-installed with XDebug and pre-configured for PHPStorm debugger. All you need to do is set folder mapping within
your IDE for each debuggable host.

## Error tracking

All PHP applications are preconfigured to use [Airbrake](https://airbrake.io/) error tracking - see `.env.example`
for configuration options.

We recommend to use self-hosted [Errbit](https://github.com/errbit/errbit) instance
as it's compatible with Airbrake APIs and completely open-source.

## Known issues

- PHP images are coming with preinstalled and always-on XDebug. We made the always-on choise for easier debugging and
availability to debug also console scripts. However if it can't connect to the host machine, it slows down the request
because it waits for connection timeout. Therefore is very important to have proper `XDEBUG_CONFIG` variables
configured in your `docker-compose.override.yml`. 

- Windows is pushing scripts to Docker with CRLF new lines which is causing issues described 
[in this blog](http://willi.am/blog/2016/08/11/docker-for-windows-dealing-with-windows-line-endings).
Clone your repository with extra ` --config core.autocrlf=input` parameter and set your IDE to save files with `LF` 
line endings.

- Telegraf gets stuck if requested topic doesn't exist yet. This has been reported and "hacked" with dockerize, 
custom topic creation and waits. This is not 100% bulletproof and will be fixed when Telegraf 1.4 is released.

- If you're using Docker Toolbox with VirtualBox and your workspace is outside your $HOME folder, you need to add
shared folder to your appliance so the Docker containers are able to work with shared volumes properly.

    ```
    VBoxManage.exe sharedfolder add default --automount --name 'd/gospace' --hostpath '\\?\d:\gospace'
    VBoxManage.exe sharedfolder add default --automount --name 'cygdrive/d/gospace' --hostpath '\\?\d:\gospace'
    ```

    The first command has to be run always. The second needs to be used only when you want to use CygWin instead 
    of default MinGW.
