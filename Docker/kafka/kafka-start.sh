#!/bin/bash

echo "Starting kafka"
./create-topics.sh &
supervisord -n