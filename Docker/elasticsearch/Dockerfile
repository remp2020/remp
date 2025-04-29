FROM docker.elastic.co/elasticsearch/elasticsearch:8.6.1

COPY --chmod=777 create-indexes.sh .

COPY --chmod=777 elastic-start.sh .

COPY elasticsearch.yml /usr/share/elasticsearch/config/elasticsearch.yml

CMD ["./elastic-start.sh"]
