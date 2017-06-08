#!/bin/sh

# If you would like to do some extra provisioning you may
# add any commands you wish to this file and they will
# be run after the Homestead machine is provisioned.

echo "Adding extra sources"
if [ ! -f /etc/apt/sources.list.d/influxdb.list ]; then
    curl -sL https://repos.influxdata.com/influxdb.key | sudo apt-key add -
    source /etc/lsb-release
    echo "deb https://repos.influxdata.com/${DISTRIB_ID,,} ${DISTRIB_CODENAME} stable" | sudo tee /etc/apt/sources.list.d/influxdb.list
    sudo apt-get update
fi
if ! grep "apt.dockerproject.org" -r /etc/apt > /dev/null; then
    sudo apt-key adv --keyserver hkp://p80.pool.sks-keyservers.net:80 --recv-keys 58118E89F3A912897C070ADBF76221572C52609D
    sudo apt-add-repository 'deb https://apt.dockerproject.org/repo ubuntu-xenial main'
    sudo apt-get update
fi

echo "Verifying Docker installation"
if ! type docker > /dev/null; then
    sudo apt-get install -y docker-engine
    sudo systemctl enable docker
    sudo systemctl start docker
    sudo usermod -aG docker $(whoami) # vyzera, ze nefunguje dobre
fi

echo "Verifying InfluxDB installation"
if ! type influx > /dev/null; then
    sudo apt-get -y install influxdb
    sudo systemctl enable influxd
    sudo systemctl start influxd
fi

echo "Verifying Kafka installation"
if ! dir /usr/local/kafka > /dev/null; then
    sudo apt-get update
    sudo apt-get -y install default-jre zookeeperd
    wget http://tux.rainside.sk/apache/kafka/0.10.2.0/kafka_2.12-0.10.2.0.tgz
    tar xvf kafka_2.12-0.10.2.0.tgz
    sudo mv kafka_2.12-0.10.2.0 /usr/local/kafka
    echo "/usr/local/kafka/bin/kafka-server-start.sh /usr/local/kafka/config/server.properties" | tee /home/vagrant/kafka-start.sh
    chmod +x /home/vagrant/kafka-start.sh
fi

echo "Verifying Telegraf installation"
if ! type telegraf > /dev/null; then
    sudo apt-get -y install telegraf
    sudo systemctl enable telegraf
    sudo systemctl start telegraf
fi