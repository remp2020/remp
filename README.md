# REMP

## Beam

Beam serves as a tool for tracking events via API. Endpoints can be discovered via generated swagger.json.

To build it, you need to have Go 1.8 installed and any kind of GNU make support (Unix-based or 
cygwin on Windows). After that, execute:

    make build
    
If you don't want to install Go, you can fully use Docker to run all tools together. 
Feel free to alter `docker-compose.yml` based on your needs. Then run:

    make docker-compose
    docker-compose up
    
After that, the APIs are exposed:

    http://localhost:8081/swagger.json # beam Swagger
    
### Dependencies

- Go 1.8
- InfluxDB 1.2
- Telegraf 1.2
- Kafka 0.10
- Zookeeper 3.4
    
### Known issues

- After first start of Docker Compose, Telegraf doesn't handle non-existing Kafka topic correctly and gets stuck. 
Restarting Telegraf after topic is ready (it's created automatically) helps.

- If you're using Docker Toolbox with VirtualBox, you need to add shared folder to your appliance so the Docker containers
are able to work with shared volumes properly.

```
VBoxManage.exe sharedfolder add default --automount --name "d/gospace" --hostpath "\\?\d:\gospace"
VBoxManage.exe sharedfolder add default --automount --name "cygdrive/d/gospace" --hostpath "\\?\d:\gospace"
```