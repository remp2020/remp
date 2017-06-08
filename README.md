# REMP

## Beam

See separate documentation in [Beam](Beam).

### Known issues

- After first start of Docker Compose, Telegraf doesn't handle non-existing Kafka topic correctly and gets stuck. 
Restarting Telegraf after topic is ready (it's created automatically) helps.

- If you're using Docker Toolbox with VirtualBox, you need to add shared folder to your appliance so the Docker containers
are able to work with shared volumes properly.

    ```
    VBoxManage.exe sharedfolder add default --automount --name 'd/gospace' --hostpath '\\?\d:\gospace'
    VBoxManage.exe sharedfolder add default --automount --name 'cygdrive/d/gospace' --hostpath '\\?\d:\gospace'
    ```

    The first command has to be run always. The second needs to be used only when you want to use CygWin instead of default MinGW.
