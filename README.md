# REMP

## Apps

See separate documentations of each app:
* [Beam](Beam).
* [Campaign](Campaign).
* [Mailer](Mailer).

## Running

We've prepared `docker-compose.yml` in a way it's ready for development. You can run all or just selected services by calling `docker-compose up`.

If you're unfamiliar with `docker-compose`, try running `docker-compose --help` as a starter. Each of the subcommands of Docker also supports its own `--help` switch. Feel free to explore it.

Couple of neat commands:
* `docker-compose down` to remove all containers, networks and volumes created by `docker-compose`
* `docker-compose ps` to list all services with their status
* `docker-compose logs` to read services logs
* `docker images` to list all available docker images
* `docker images | grep remp_ | awk '{print $1}' | xargs docker rmi` to remove all custom remp images (important when rebuilding)

## PHP Debugging

Docker compose and custom images are ready for PHPStorm debugger. All you need to do is set folder for each debuggable host.

## Known issues

- Telegraf gets stuck if requested topic doesn't exist yet. This has been reported and "hacked" with dockerize, custom topic creation and waits.

- If you're using Docker Toolbox with VirtualBox, you need to add shared folder to your appliance so the Docker containers
are able to work with shared volumes properly.

    ```
    VBoxManage.exe sharedfolder add default --automount --name 'd/gospace' --hostpath '\\?\d:\gospace'
    VBoxManage.exe sharedfolder add default --automount --name 'cygdrive/d/gospace' --hostpath '\\?\d:\gospace'
    ```

    The first command has to be run always. The second needs to be used only when you want to use CygWin instead of default MinGW.
