#!/bin/sh

# If you would like to do some extra provisioning you may
# add any commands you wish to this file and they will
# be run after the Homestead machine is provisioned.

echo "Verifying Kafka installation"
if ! dir /usr/local/kafka > /dev/null
then
    sudo apt-get update
    sudo apt-get -y install default-jre zookeeperd
    wget http://tux.rainside.sk/apache/kafka/0.10.2.0/kafka_2.12-0.10.2.0.tgz
    tar xvf kafka_2.12-0.10.2.0.tgz
    sudo mv kafka_2.12-0.10.2.0 /usr/local/kafka
    echo "/usr/local/kafka/bin/kafka-server-start.sh /usr/local/kafka/config/server.properties" | tee /home/vagrant/kafka-start.sh
    chmod +x /home/vagrant/kafka-start.sh
fi