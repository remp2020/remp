#!/bin/bash

for i in {30..0}; do
    if curl elasticsearch:9200; then
        curl -s -XPUT -H "Content-Type: application/json" elasticsearch:9200/commerce -d '{ "mappings": { "_doc": { "properties": { "revenue": { "type": "double" } } } } }';
        curl -s -XPUT -H "Content-Type: application/json" elasticsearch:9200/events -d '{ "mappings": { "_doc": {  } } }';
        curl -s -XPUT -H "Content-Type: application/json" elasticsearch:9200/pageviews -d '{ "mappings": { "_doc": {  } } }';
        curl -s -XPUT -H "Content-Type: application/json" elasticsearch:9200/pageviews_time_spent -d '{ "mappings": { "_doc": {  } } }';
        curl -s -XPUT -H "Content-Type: application/json" elasticsearch:9200/concurrents_by_browser -d '{ "mappings": { "_doc": {  } } }';
        break;
    fi
    sleep 2
done
