If you're using Docker Toolbox with VirtualBox, you need to add shared folder to your appliance so the Docker containers
are able to work with shared volumes properly.

    VBoxManage.exe sharedfolder add default --automount --name "d/gospace" --hostpath "\\?\d:\gospace"
    VBoxManage.exe sharedfolder add default --automount --name "cygdrive/d/gospace" --hostpath "\\?\d:\gospace"