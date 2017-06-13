FROM spotify/kafka

RUN apt-get update

RUN apt-get -y install net-tools

ADD create-topics.sh .

RUN chmod +x create-topics.sh

ADD kafka-start.sh .

RUN chmod +x kafka-start.sh

CMD ["./kafka-start.sh"]