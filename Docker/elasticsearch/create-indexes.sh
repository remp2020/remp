#!/bin/bash

for i in {30..0}; do
    if curl elasticsearch:9200; then
        curl -s -XPUT -H "Content-Type: application/json" elasticsearch:9200/commerce/_doc -d '{ "mappings": {"properties": { "revenue": { "type": "double" } } } }';
        curl -s -XPUT -H "Content-Type: application/json" elasticsearch:9200/events/_doc -d '{ "mappings": {  } }';
        curl -s -XPUT -H "Content-Type: application/json" elasticsearch:9200/pageviews/_doc -d '{ "mappings": {  } }';
        curl -s -XPUT -H "Content-Type: application/json" elasticsearch:9200/pageviews_time_spent/_doc -d '{ "mappings": {  } }';
        curl -s -XPUT -H "Content-Type: application/json" elasticsearch:9200/concurrents_by_browser/_doc -d '{ "mappings": {  } }';
        break;
    fi
    sleep 2
done
