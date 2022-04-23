FROM docker.elastic.co/elasticsearch/elasticsearch:7.17.3

ADD create-indexes.sh .
RUN chmod +x create-indexes.sh

ADD elastic-start.sh .
RUN chmod +x elastic-start.sh

CMD ["./elastic-start.sh"]
