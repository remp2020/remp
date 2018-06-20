#!/bin/bash

for i in {30..0}; do
    if curl elasticsearch:9200; then
        curl -XPUT -H "Content-Type: application/json" elasticsearch:9200/commerce --data-binary @commerce.json;
        curl -XPUT -H "Content-Type: application/json" elasticsearch:9200/events;
        curl -XPUT -H "Content-Type: application/json" elasticsearch:9200/pageviews;
        curl -XPUT -H "Content-Type: application/json" elasticsearch:9200/pageviews_time_spent;
            break;
    fi
    sleep 2
done
