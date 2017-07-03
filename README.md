# REMP

## Apps

See separate documentations of each app:
* [Beam](Beam)
* [Campaign](Campaign)
* [Mailer](Mailer)

## Running

We've prepared `docker-compose.yml` in a way it's ready for development.
 
There's a need for pre-building binaries of Go apps before you can run Docker compose. You don't need Go environment to have
set up, but you need Docker to build docker-ready tarballs properly.

```bash
cd Beam
make docker-build
```

After that you can run all or just selected services by calling `docker-compose up`.

Application exposes all services via Nginx container.

Following is list of available hosts. We advise you to add them to your `/etc/hosts`:

```bash
# CAMPAIGN
campaign.remp.app # web administration

# MAILER
mailer.remp.app # web administration

# BEAM
beam.remp.app # web administration
tracker.beam.remp.app # event tracker API; swagger @ http://tracker.beam.remp.app/swagger.json
segments.beam.remp.app # segments API; swagger @ http://segments.beam.remp.app/swagger.json

# SERVICE APPS
adminer.remp.app # adminer for manipulating with DB
mailhog.remp.app # mailhog for catching and debugging sent emails
```

### Docker Compose

If you're unfamiliar with `docker-compose`, try running `docker-compose --help` as a starter. Each of the subcommands of Docker also supports its own `--help` switch. Feel free to explore it.

Couple of neat commands:
* `docker-compose down` to remove all containers, networks and volumes created by `docker-compose`
* `docker-compose ps` to list all services with their status
* `docker-compose logs` to read services logs
* `docker images` to list all available docker images
* `docker images | grep remp_ | awk '{print $1}' | xargs docker rmi` to remove all custom remp images (important when rebuilding)

## PHP Debugging

Docker compose and custom images are ready for PHPStorm debugger. All you need to do is set folder mapping within your IDE
for each debuggable host.

## Known issues

- Windows is pushing scripts to Docker with CRLF new lines which is causing issues described [in this blog](http://willi.am/blog/2016/08/11/docker-for-windows-dealing-with-windows-line-endings).
Clone your repository with extra ` --config core.autocrlf=input` parameter and set your IDE to save files with `LF` line endings.

- Telegraf gets stuck if requested topic doesn't exist yet. This has been reported and "hacked" with dockerize, custom topic creation and waits.

- If you're using Docker Toolbox with VirtualBox, you need to add shared folder to your appliance so the Docker containers
are able to work with shared volumes properly.

    ```
    VBoxManage.exe sharedfolder add default --automount --name 'd/gospace' --hostpath '\\?\d:\gospace'
    VBoxManage.exe sharedfolder add default --automount --name 'cygdrive/d/gospace' --hostpath '\\?\d:\gospace'
    ```

    The first command has to be run always. The second needs to be used only when you want to use CygWin instead of default MinGW.
